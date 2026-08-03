<?php

declare(strict_types=1);

namespace Wacdo\Modeles;

use PDOException;
use Wacdo\Core\Modele;

/**
 * Dépôt (repository) pour la table `produit` + `produit_taille` (T05.1).
 */
final class ProduitDepot extends Modele
{
    /** @return array<int, Produit> */
    public function trouverTous(): array
    {
        $lignes = $this->pdo->query(
            'SELECT p.id, p.nom, p.description, p.prix, p.categorie_id, c.nom AS categorie_nom, p.image, p.disponible
             FROM produit p
             JOIN categorie c ON c.id = p.categorie_id
             ORDER BY c.nom, p.nom'
        )->fetchAll();

        return array_map(fn (array $ligne) => $this->hydrater($ligne), $lignes);
    }

    public function trouverParId(int $id): ?Produit
    {
        $requete = $this->pdo->prepare(
            'SELECT p.id, p.nom, p.description, p.prix, p.categorie_id, c.nom AS categorie_nom, p.image, p.disponible
             FROM produit p
             JOIN categorie c ON c.id = p.categorie_id
             WHERE p.id = :id'
        );
        $requete->execute(['id' => $id]);
        $ligne = $requete->fetch();

        return $ligne === false ? null : $this->hydrater($ligne);
    }

    public function creer(string $nom, ?string $description, ?float $prix, int $categorieId, ?string $image, bool $disponible): int
    {
        $requete = $this->pdo->prepare(
            'INSERT INTO produit (nom, description, prix, categorie_id, image, disponible)
             VALUES (:nom, :description, :prix, :categorie_id, :image, :disponible)'
        );
        $requete->execute([
            'nom'          => $nom,
            'description'  => $description,
            'prix'         => $prix,
            'categorie_id' => $categorieId,
            'image'        => $image,
            'disponible'   => $disponible ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function modifier(int $id, string $nom, ?string $description, ?float $prix, int $categorieId, ?string $image, bool $disponible): void
    {
        $requete = $this->pdo->prepare(
            'UPDATE produit
             SET nom = :nom, description = :description, prix = :prix,
                 categorie_id = :categorie_id, image = :image, disponible = :disponible
             WHERE id = :id'
        );
        $requete->execute([
            'nom'          => $nom,
            'description'  => $description,
            'prix'         => $prix,
            'categorie_id' => $categorieId,
            'image'        => $image,
            'disponible'   => $disponible ? 1 : 0,
            'id'           => $id,
        ]);
    }

    /**
     * Remplace entièrement les prix par taille d'un produit
     * (ex. [1 => 2.20, 2 => 2.70] pour Petite/Grande).
     *
     * @param array<int, float> $prixParTailleId
     */
    public function definirTailles(int $produitId, array $prixParTailleId): void
    {
        $this->pdo->prepare('DELETE FROM produit_taille WHERE produit_id = :id')->execute(['id' => $produitId]);

        if ($prixParTailleId === []) {
            return;
        }

        $requete = $this->pdo->prepare(
            'INSERT INTO produit_taille (produit_id, taille_id, prix) VALUES (:produit_id, :taille_id, :prix)'
        );

        foreach ($prixParTailleId as $tailleId => $prix) {
            $requete->execute(['produit_id' => $produitId, 'taille_id' => $tailleId, 'prix' => $prix]);
        }
    }

    public function basculerDisponibilite(int $id): void
    {
        $this->pdo->prepare('UPDATE produit SET disponible = 1 - disponible WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * @return true|string true si la suppression a réussi, sinon un message d'erreur
     */
    public function supprimer(int $id): bool|string
    {
        try {
            $this->pdo->prepare('DELETE FROM produit_taille WHERE produit_id = :id')->execute(['id' => $id]);
            $this->pdo->prepare('DELETE FROM produit WHERE id = :id')->execute(['id' => $id]);

            return true;
        } catch (PDOException) {
            return 'Ce produit est utilisé dans un menu ou une commande existante : impossible de le supprimer (le désactiver à la place).';
        }
    }

    /**
     * @param array<string, mixed> $ligne
     */
    private function hydrater(array $ligne): Produit
    {
        return new Produit(
            (int) $ligne['id'],
            (string) $ligne['nom'],
            $ligne['description'] !== null ? (string) $ligne['description'] : null,
            $ligne['prix'] !== null ? (float) $ligne['prix'] : null,
            (int) $ligne['categorie_id'],
            (string) $ligne['categorie_nom'],
            $ligne['image'] !== null ? (string) $ligne['image'] : null,
            (bool) $ligne['disponible'],
            $this->tailles((int) $ligne['id']),
        );
    }

    /**
     * @return array<int, array{tailleId:int, libelle:string, prix:float}>
     */
    private function tailles(int $produitId): array
    {
        $requete = $this->pdo->prepare(
            'SELECT t.id AS taille_id, t.libelle, pt.prix
             FROM produit_taille pt
             JOIN taille t ON t.id = pt.taille_id
             WHERE pt.produit_id = :id
             ORDER BY t.id'
        );
        $requete->execute(['id' => $produitId]);

        return array_map(
            static fn (array $ligne) => [
                'tailleId' => (int) $ligne['taille_id'],
                'libelle'  => (string) $ligne['libelle'],
                'prix'     => (float) $ligne['prix'],
            ],
            $requete->fetchAll()
        );
    }
}
