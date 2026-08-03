<?php

declare(strict_types=1);

namespace Wacdo\Securite;

use Wacdo\Core\Routeur;
use Wacdo\Modeles\Utilisateur;

/**
 * Authentification et contrôle d'accès par rôle (T04.3 à T04.5).
 * Fait office de "middleware" : à appeler en tête des actions de contrôleur
 * qui doivent être protégées.
 */
final class Auth
{
    private const CLE_SESSION = 'utilisateur';

    public static function connecter(Utilisateur $utilisateur): void
    {
        // Change l'identifiant de session après authentification pour
        // empêcher toute fixation de session (bonne pratique OWASP).
        session_regenerate_id(true);
        $_SESSION[self::CLE_SESSION] = $utilisateur->versSession();
    }

    public static function deconnecter(): void
    {
        unset($_SESSION[self::CLE_SESSION]);
        session_regenerate_id(true);
    }

    public static function estConnecte(): bool
    {
        return isset($_SESSION[self::CLE_SESSION]);
    }

    /**
     * @return array{id:int, nom:string, prenom:string, email:string, role:string, libelleRole:string}|null
     */
    public static function utilisateurConnecte(): ?array
    {
        return $_SESSION[self::CLE_SESSION] ?? null;
    }

    /**
     * À appeler en tête d'une action protégée : redirige vers /connexion si
     * personne n'est authentifié (T04.5).
     */
    public static function exigerConnexion(): void
    {
        if (self::estConnecte()) {
            return;
        }

        header('Location: ' . Routeur::urlBase() . '/connexion');
        exit;
    }

    /**
     * À appeler en tête d'une action protégée par module : exige d'abord une
     * connexion, puis répond 403 si le rôle connecté n'a pas accès (T04.5).
     */
    public static function exigerModule(string $module): void
    {
        self::exigerConnexion();

        $role = $_SESSION[self::CLE_SESSION]['role'];

        if (!Utilisateur::depuisRole($role)->peutAccederA($module)) {
            http_response_code(403);
            require dirname(__DIR__) . '/Vues/erreurs/403.php';
            exit;
        }
    }
}
