<?php

declare(strict_types=1);

namespace Wacdo\Modeles;

use PDOException;
use Wacdo\Core\Modele;

/**
 * Dépôt (repository) pour la table `menu` (T05.6).
 */
final class MenuDepot extends Modele
{
    /** @return array<int, Menu> */
    public function trouverTous(): array
    {
        $lignes = $this->pdo->query(
            'SELECT m.id, m.nom, m.description, m.prix_base, m.burger_id, p.nom AS burger_nom, m.image, m.disponible
             FROM menu m
             JOIN produit p ON p.id = m.burger_id
             ORDER BY m.nom'
        )->fetchAll();

        return array_map(self::hydrater(...), $lignes);
    }

    public function trouverParId(int $id): ?Menu
    {
        $requete = $this->pdo->prepare(
            'SELECT m.id, m.nom, m.description, m.prix_base, m.burger_id, p.nom AS burger_nom, m.image, m.disponible
             FROM menu m
             JOIN produit p ON p.id = m.burger_id
             WHERE m.id = :id'
        );
        $requete->execute(['id' => $id]);
        $ligne = $requete->fetch();

        return $ligne === false ? null : self::hydrater($ligne);
    }

    /**
     * Burgers pouvant être associés à un menu : tout produit de catégorie
     * "burger" non déjà utilisé par un autre menu (contrainte `uq_menu_burger`).
     *
     * @return array<int, array{id:int, nom:string}>
     */
    public function burgersDisponibles(?int $menuIdActuel = null): array
    {
        $requete = $this->pdo->prepare(
            "SELECT p.id, p.nom
             FROM produit p
             JOIN categorie c ON c.id = p.categorie_id
             WHERE c.nom = 'burger'
               AND (p.id NOT IN (SELECT burger_id FROM menu) OR p.id = :burger_id_actuel)
             ORDER BY p.nom"
        );
        $requete->execute(['burger_id_actuel' => $menuIdActuel !== null ? $this->burgerIdDuMenu($menuIdActuel) : 0]);

        return array_map(
            static fn (array $l) => ['id' => (int) $l['id'], 'nom' => (string) $l['nom']],
            $requete->fetchAll()
        );
    }

    private function burgerIdDuMenu(int $menuId): int
    {
        $requete = $this->pdo->prepare('SELECT burger_id FROM menu WHERE id = :id');
        $requete->execute(['id' => $menuId]);
        $burgerId = $requete->fetchColumn();

        return $burgerId !== false ? (int) $burgerId : 0;
    }

    public function creer(string $nom, ?string $description, float $prixBase, int $burgerId, ?string $image, bool $disponible): int
    {
        $requete = $this->pdo->prepare(
            'INSERT INTO menu (nom, description, prix_base, burger_id, image, disponible)
             VALUES (:nom, :description, :prix_base, :burger_id, :image, :disponible)'
        );
        $requete->execute([
            'nom'         => $nom,
            'description' => $description,
            'prix_base'   => $prixBase,
            'burger_id'   => $burgerId,
            'image'       => $image,
            'disponible'  => $disponible ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function modifier(int $id, string $nom, ?string $description, float $prixBase, int $burgerId, ?string $image, bool $disponible): void
    {
        $requete = $this->pdo->prepare(
            'UPDATE menu
             SET nom = :nom, description = :description, prix_base = :prix_base,
                 burger_id = :burger_id, image = :image, disponible = :disponible
             WHERE id = :id'
        );
        $requete->execute([
            'nom'         => $nom,
            'description' => $description,
            'prix_base'   => $prixBase,
            'burger_id'   => $burgerId,
            'image'       => $image,
            'disponible'  => $disponible ? 1 : 0,
            'id'          => $id,
        ]);
    }

    /**
     * @return true|string true si la suppression a réussi, sinon un message d'erreur
     */
    public function supprimer(int $id): bool|string
    {
        try {
            $this->pdo->prepare('DELETE FROM menu WHERE id = :id')->execute(['id' => $id]);

            return true;
        } catch (PDOException) {
            return 'Ce menu est référencé par une commande existante : impossible de le supprimer (le désactiver à la place).';
        }
    }

    /**
     * @param array<string, mixed> $ligne
     */
    private static function hydrater(array $ligne): Menu
    {
        return new Menu(
            (int) $ligne['id'],
            (string) $ligne['nom'],
            $ligne['description'] !== null ? (string) $ligne['description'] : null,
            (float) $ligne['prix_base'],
            (int) $ligne['burger_id'],
            (string) $ligne['burger_nom'],
            $ligne['image'] !== null ? (string) $ligne['image'] : null,
            (bool) $ligne['disponible'],
        );
    }
}
