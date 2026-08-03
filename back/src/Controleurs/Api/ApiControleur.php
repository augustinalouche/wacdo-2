<?php

declare(strict_types=1);

namespace Wacdo\Controleurs\Api;

use InvalidArgumentException;
use Wacdo\Core\Controleur;

/**
 * Classe de base des contrôleurs de l'API (`EPIC 8`) : réponses JSON
 * cohérentes (`T08.4`) et lecture du corps JSON des requêtes `POST`.
 */
abstract class ApiControleur extends Controleur
{
    /** Réponse "brute" : la donnée EST le corps JSON (pas d'enveloppe), conforme à `docs/conception/04-structure-json.md`. */
    protected function reussite(mixed $donnees, int $codeHttp = 200): void
    {
        $this->repondreJson($donnees, $codeHttp);
    }

    /** Corps d'erreur structuré, identique quel que soit le code HTTP renvoyé. */
    protected function echec(string $message, int $codeHttp = 400): void
    {
        $this->repondreJson(['succes' => false, 'erreur' => $message], $codeHttp);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException si le corps de la requête n'est pas du JSON valide
     */
    protected function corpsJson(): array
    {
        $brut = file_get_contents('php://input');
        $donnees = json_decode($brut !== false ? $brut : '', true);

        if (!is_array($donnees)) {
            throw new InvalidArgumentException('Corps de requête JSON invalide.');
        }

        return $donnees;
    }
}
