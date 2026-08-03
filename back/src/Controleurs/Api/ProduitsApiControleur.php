<?php

declare(strict_types=1);

namespace Wacdo\Controleurs\Api;

use Wacdo\Modeles\Produit;
use Wacdo\Modeles\ProduitDepot;

/**
 * `GET /api/produits` (T08.1) — même structure que `front/data/produits.json`
 * (voir `docs/conception/04-structure-json.md`), pour que le front puisse
 * basculer du fichier statique vers l'API réelle sans rien changer (T10.1).
 */
final class ProduitsApiControleur extends ApiControleur
{
    public function index(): void
    {
        $categorie = trim((string) ($_GET['categorie'] ?? ''));
        $produits = (new ProduitDepot())->trouverTous();

        if ($categorie !== '') {
            $produits = array_values(array_filter(
                $produits,
                static fn (Produit $produit) => $produit->categorieNom === $categorie
            ));
        }

        $this->reussite(array_map($this->versJson(...), $produits));
    }

    /** @return array<string, mixed> */
    private function versJson(Produit $produit): array
    {
        return [
            'id'          => $produit->id,
            'nom'         => $produit->nom,
            'description' => $produit->description,
            'categorie'   => $produit->categorieNom,
            'image'       => $produit->image,
            'disponible'  => $produit->disponible,
            'prix'        => $produit->utiliseDesTailles() ? $this->prixParTaille($produit) : $produit->prix,
        ];
    }

    /** @return array{petite: float, grande: float} */
    private function prixParTaille(Produit $produit): array
    {
        $prix = [];
        foreach ($produit->tailles as $taille) {
            $prix[mb_strtolower($taille['libelle'])] = $taille['prix'];
        }

        return $prix;
    }
}
