<?php

declare(strict_types=1);

namespace Wacdo\Core;

use RuntimeException;

/**
 * Upload d'image produit/menu (T05.5). Les fichiers sont enregistrés
 * directement dans `front/img/` : le mono-dépôt front+back partage ainsi les
 * mêmes visuels sans étape de synchronisation, et la colonne `image` en base
 * reste un simple nom de fichier (cohérent avec `docs/conception/04-structure-json.md`).
 */
final class TeleversementImage
{
    /** @var array<int, string> */
    private const EXTENSIONS_AUTORISEES = ['png', 'jpg', 'jpeg', 'webp'];
    private const TAILLE_MAX_OCTETS = 2 * 1024 * 1024;

    /**
     * @param array{name:string, type:string, tmp_name:string, error:int, size:int} $fichier une entrée de $_FILES
     * @throws RuntimeException si le fichier est invalide
     */
    public static function enregistrer(array $fichier, string $prefixe): string
    {
        if ($fichier['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("L'envoi du fichier a échoué (code {$fichier['error']}).");
        }

        if ($fichier['size'] > self::TAILLE_MAX_OCTETS) {
            throw new RuntimeException('Image trop volumineuse (2 Mo maximum).');
        }

        $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, self::EXTENSIONS_AUTORISEES, true)) {
            throw new RuntimeException('Format d\'image non autorisé (png, jpg, jpeg, webp uniquement).');
        }

        $prefixeSain = preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($prefixe)) ?? 'image';
        $nomFichier = trim($prefixeSain, '-') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;

        $dossierDestination = self::dossierImages();
        if (!is_dir($dossierDestination) && !mkdir($dossierDestination, 0775, true) && !is_dir($dossierDestination)) {
            throw new RuntimeException("Le dossier de destination n'a pas pu être créé.");
        }

        if (!move_uploaded_file($fichier['tmp_name'], $dossierDestination . $nomFichier)) {
            throw new RuntimeException("L'enregistrement de l'image a échoué.");
        }

        return $nomFichier;
    }

    private static function dossierImages(): string
    {
        // back/src/Core -> back/src -> back -> racine du dépôt -> front/img
        return dirname(__DIR__, 3) . '/front/img/';
    }
}
