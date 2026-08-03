<?php

declare(strict_types=1);

namespace Wacdo\Modeles;

use Wacdo\Core\Modele;

/**
 * Accès aux tables de référence (catégorie, taille, sauce, statut) — utilisées
 * par plusieurs modules (produits, menus, futures commandes en EPIC 7).
 */
final class ReferentielDepot extends Modele
{
    /** @return array<int, array{id:int, nom:string}> */
    public function categories(): array
    {
        $lignes = $this->pdo->query('SELECT id, nom FROM categorie ORDER BY nom')->fetchAll();

        return array_map(
            static fn (array $l) => ['id' => (int) $l['id'], 'nom' => (string) $l['nom']],
            $lignes
        );
    }

    /**
     * PDO renvoie toujours les colonnes DECIMAL sous forme de chaîne (jamais
     * de type float natif, quel que soit le pilote) — on caste donc ici pour
     * que les vues (en `strict_types=1`) puissent les passer à `number_format`.
     *
     * @return array<int, array{id:int, libelle:string, supplement:float}>
     */
    public function tailles(): array
    {
        $lignes = $this->pdo->query('SELECT id, libelle, supplement FROM taille ORDER BY id')->fetchAll();

        return array_map(
            static fn (array $l) => [
                'id'         => (int) $l['id'],
                'libelle'    => (string) $l['libelle'],
                'supplement' => (float) $l['supplement'],
            ],
            $lignes
        );
    }

    /** @return array<int, array{id:int, nom:string}> */
    public function sauces(): array
    {
        $lignes = $this->pdo->query('SELECT id, nom FROM sauce ORDER BY nom')->fetchAll();

        return array_map(
            static fn (array $l) => ['id' => (int) $l['id'], 'nom' => (string) $l['nom']],
            $lignes
        );
    }

    /** @return array<int, array{id:int, libelle:string}> */
    public function statuts(): array
    {
        $lignes = $this->pdo->query('SELECT id, libelle FROM statut ORDER BY id')->fetchAll();

        return array_map(
            static fn (array $l) => ['id' => (int) $l['id'], 'libelle' => (string) $l['libelle']],
            $lignes
        );
    }
}
