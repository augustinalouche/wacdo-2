<?php

declare(strict_types=1);

namespace Wacdo\Modeles;

use PDO;
use PDOException;
use Wacdo\Core\Modele;

/**
 * Dépôt (repository) pour la table `utilisateur` (T04.1, T04.2, T06.1) : seule
 * classe à parler SQL pour les utilisateurs. Elle renvoie des objets
 * `Utilisateur` (via `Utilisateur::depuisLigne`), jamais des tableaux bruts.
 */
final class UtilisateurDepot extends Modele
{
    /** @return array<int, Utilisateur> */
    public function trouverTous(): array
    {
        $lignes = $this->pdo->query(
            'SELECT id, nom, prenom, email, mot_de_passe, role, actif
             FROM utilisateur
             ORDER BY nom, prenom'
        )->fetchAll();

        return array_map(Utilisateur::depuisLigne(...), $lignes);
    }

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

    /**
     * @param string|null $motDePasseClair si null (ou vide), le mot de passe existant est conservé (T06.1).
     */
    public function modifier(int $id, string $nom, string $prenom, string $email, string $role, bool $actif, ?string $motDePasseClair = null): void
    {
        $champsMotDePasse = '';
        $parametres = [
            'nom'    => $nom,
            'prenom' => $prenom,
            'email'  => $email,
            'role'   => $role,
            'actif'  => $actif ? 1 : 0,
            'id'     => $id,
        ];

        if ($motDePasseClair !== null && $motDePasseClair !== '') {
            $champsMotDePasse = ', mot_de_passe = :mot_de_passe';
            $parametres['mot_de_passe'] = password_hash($motDePasseClair, PASSWORD_DEFAULT);
        }

        $requete = $this->pdo->prepare(
            "UPDATE utilisateur
             SET nom = :nom, prenom = :prenom, email = :email, role = :role, actif = :actif{$champsMotDePasse}
             WHERE id = :id"
        );
        $requete->execute($parametres);
    }

    public function basculerActif(int $id): void
    {
        $this->pdo->prepare('UPDATE utilisateur SET actif = 1 - actif WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * @return true|string true si la suppression a réussi, sinon un message d'erreur
     */
    public function supprimer(int $id): bool|string
    {
        try {
            $this->pdo->prepare('DELETE FROM utilisateur WHERE id = :id')->execute(['id' => $id]);

            return true;
        } catch (PDOException) {
            return "Cet utilisateur est référencé par une commande existante : impossible de le supprimer (le désactiver à la place).";
        }
    }

    public function emailExiste(string $email, ?int $idAExclure = null): bool
    {
        $requete = $this->pdo->prepare(
            'SELECT COUNT(*) FROM utilisateur WHERE email = :email AND id <> :id_a_exclure'
        );
        $requete->execute(['email' => $email, 'id_a_exclure' => $idAExclure ?? 0]);

        return ((int) $requete->fetchColumn()) > 0;
    }
}
