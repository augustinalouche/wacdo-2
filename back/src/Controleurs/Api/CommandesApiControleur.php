<?php

declare(strict_types=1);

namespace Wacdo\Controleurs\Api;

use InvalidArgumentException;
use RuntimeException;
use Wacdo\Modeles\CommandeDepot;

/**
 * `POST /api/commandes` (T08.3) — réception du JSON envoyé par la borne
 * (voir `docs/conception/04-structure-json.md`), validation serveur complète
 * et insertion en base. Le prix n'est **jamais** accepté depuis le JSON reçu :
 * `CommandeDepot::creer()` le recalcule intégralement à partir du catalogue
 * (même règle que la saisie comptoir/téléphone de `EPIC 7`).
 */
final class CommandesApiControleur extends ApiControleur
{
    private const ORIGINES_VALIDES = ['borne', 'comptoir', 'telephone'];
    private const LONGUEUR_MAX_NUMERO = 20;

    public function creer(): void
    {
        try {
            $donnees = $this->corpsJson();
        } catch (InvalidArgumentException) {
            $this->echec('Corps de requête JSON invalide.', 400);

            return;
        }

        $numero = trim((string) ($donnees['numero'] ?? ''));
        $origine = (string) ($donnees['origine'] ?? '');
        $lignesBrutes = $donnees['lignes'] ?? null;

        if ($numero === '') {
            $this->echec('Le numéro de commande est obligatoire.', 400);

            return;
        }
        if (mb_strlen($numero) > self::LONGUEUR_MAX_NUMERO) {
            $this->echec('Le numéro de commande est trop long (' . self::LONGUEUR_MAX_NUMERO . ' caractères maximum).', 400);

            return;
        }
        if (!in_array($origine, self::ORIGINES_VALIDES, true)) {
            $this->echec('Origine de commande invalide (attendu : ' . implode(', ', self::ORIGINES_VALIDES) . ').', 400);

            return;
        }
        if (!is_array($lignesBrutes) || $lignesBrutes === []) {
            $this->echec('La commande doit contenir au moins une ligne (produit ou menu).', 400);

            return;
        }

        [$lignes, $erreurValidation] = $this->validerEtNormaliserLignes($lignesBrutes);
        if ($erreurValidation !== null) {
            $this->echec($erreurValidation, 400);

            return;
        }

        try {
            // Commande issue de la borne : pas d'utilisateur back-office associé.
            $commandeId = (new CommandeDepot())->creer($numero, $origine, $lignes, null);
        } catch (RuntimeException $exception) {
            $this->echec($exception->getMessage(), 400);

            return;
        }

        $this->reussite([
            'succes'     => true,
            'commandeId' => $commandeId,
            'numero'     => $numero,
            'message'    => 'Commande enregistrée.',
        ], 201);
    }

    /**
     * Traduit et valide les lignes brutes reçues en JSON vers la structure
     * attendue par `CommandeDepot::creer()`.
     *
     * @param array<int, mixed> $lignesBrutes
     * @return array{0: array<int, array<string, mixed>>, 1: string|null}
     */
    private function validerEtNormaliserLignes(array $lignesBrutes): array
    {
        $lignes = [];

        foreach ($lignesBrutes as $ligneBrute) {
            if (!is_array($ligneBrute)) {
                return [[], 'Chaque ligne de commande doit être un objet JSON.'];
            }

            $quantite = (int) ($ligneBrute['quantite'] ?? 0);
            if ($quantite <= 0) {
                return [[], 'La quantité de chaque ligne doit être un entier strictement positif.'];
            }

            $type = $ligneBrute['type'] ?? null;

            if ($type === 'produit') {
                if (!isset($ligneBrute['produitId']) || !is_numeric($ligneBrute['produitId'])) {
                    return [[], 'Identifiant de produit manquant ou invalide sur une ligne de type "produit".'];
                }

                $tailleId = $ligneBrute['tailleId'] ?? null;
                if ($tailleId !== null && !is_numeric($tailleId)) {
                    return [[], 'Identifiant de taille invalide sur une ligne de type "produit".'];
                }

                $lignes[] = [
                    'type'      => 'produit',
                    'produitId' => (int) $ligneBrute['produitId'],
                    'tailleId'  => $tailleId !== null ? (int) $tailleId : null,
                    'quantite'  => $quantite,
                ];

                continue;
            }

            if ($type === 'menu') {
                if (!isset($ligneBrute['menuId']) || !is_numeric($ligneBrute['menuId'])) {
                    return [[], 'Identifiant de menu manquant ou invalide sur une ligne de type "menu".'];
                }

                $composition = $ligneBrute['composition'] ?? null;
                if (!is_array($composition)) {
                    return [[], 'Composition manquante sur une ligne de type "menu" (accompagnement, boisson, sauce).'];
                }

                $compositionNormalisee = [];
                foreach (['accompagnementProduitId', 'accompagnementTailleId', 'boissonProduitId', 'boissonTailleId', 'sauceId'] as $champ) {
                    if (!isset($composition[$champ]) || !is_numeric($composition[$champ])) {
                        return [[], "Champ de composition manquant ou invalide : {$champ}."];
                    }
                    $compositionNormalisee[$champ] = (int) $composition[$champ];
                }

                $lignes[] = [
                    'type'        => 'menu',
                    'menuId'      => (int) $ligneBrute['menuId'],
                    'quantite'    => $quantite,
                    'composition' => $compositionNormalisee,
                ];

                continue;
            }

            return [[], 'Type de ligne de commande inconnu (attendu "produit" ou "menu").'];
        }

        return [$lignes, null];
    }
}
