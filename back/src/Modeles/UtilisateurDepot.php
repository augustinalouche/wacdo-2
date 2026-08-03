<?php

declare(strict_types=1);

namespace Wacdo\Modeles;

use PDO;
use Wacdo\Core\Modele;

/**
 * Dépôt (repository) pour la table `utilisateur` (T04.1, T04.2) : seule classe
 * à parler SQL pour les utilisateurs. Elle renvoie des objets `Utilisateur`
 * (via `Utilisateur::depuisLigne`), jamais des tableaux bruts.
 */
final class UtilisateurDepot extends Modele
{
    public function trouverParEmail(string $email): ?Utilisateur
    {
        $requete = $this->pdo->prepare(
            'SELECT id, nom, prenom, email, mot_de_passe, role, actif
             FROM utilisateur
             WHERE email = :email'
        );
        $requete->execute(['email' => $email]);
        $ligne = $requete->fetch();

        return $ligne === false ? null : Utilisateur::depuisLigne($ligne);
    }

    public function trouverParId(int $id): ?Utilisateur
    {
        $requete = $this->pdo->prepare(
            'SELECT id, nom, prenom, email, mot_de_passe, role, actif
             FROM utilisateur
             WHERE id = :id'
        );
        $requete->execute(['id' => $id]);
        $ligne = $requete->fetch();

        return $ligne === false ? null : Utilisateur::depuisLigne($ligne);
    }

    /**
     * Hache le mot de passe (T04.2) avant insertion — utilisé par le futur
     * module de gestion des utilisateurs (EPIC 5/6).
     */
    public function creer(string $nom, string $prenom, string $email, string $motDePasseClair, string $role): int
    {
        $requete = $this->pdo->prepare(
            'INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role)
             VALUES (:nom, :prenom, :email, :mot_de_passe, :role)'
        );
        $requete->execute([
            'nom'          => $nom,
            'prenom'       => $prenom,
            'email'        => $email,
            'mot_de_passe' => password_hash($motDePasseClair, PASSWORD_DEFAULT),
            'role'         => $role,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
