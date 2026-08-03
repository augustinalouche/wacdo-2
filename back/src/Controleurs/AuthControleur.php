<?php

declare(strict_types=1);

namespace Wacdo\Controleurs;

use Wacdo\Core\Controleur;
use Wacdo\Modeles\UtilisateurDepot;
use Wacdo\Securite\Auth;
use Wacdo\Securite\Csrf;

/**
 * Connexion / déconnexion du back-office (T04.3, T04.4).
 */
final class AuthControleur extends Controleur
{
    public function formulaire(): void
    {
        if (Auth::estConnecte()) {
            $this->redirigerVers('/tableau-de-bord');

            return;
        }

        $this->afficherVue('auth/connexion', ['erreur' => null]);
    }

    public function connecter(): void
    {
        if (!Csrf::estValide($_POST['jeton_csrf'] ?? null)) {
            $this->afficherVue('auth/connexion', [
                'erreur' => 'Formulaire expiré, merci de réessayer.',
            ]);

            return;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $motDePasse = (string) ($_POST['mot_de_passe'] ?? '');

        if ($email === '' || $motDePasse === '') {
            $this->afficherVue('auth/connexion', [
                'erreur' => 'Merci de renseigner votre email et votre mot de passe.',
            ]);

            return;
        }

        $utilisateur = (new UtilisateurDepot())->trouverParEmail($email);

        if ($utilisateur === null || !$utilisateur->estActif() || !$utilisateur->verifierMotDePasse($motDePasse)) {
            $this->afficherVue('auth/connexion', [
                'erreur' => 'Email ou mot de passe incorrect.',
            ]);

            return;
        }

        Auth::connecter($utilisateur);
        $this->redirigerVers('/tableau-de-bord');
    }

    public function deconnecter(): void
    {
        Auth::deconnecter();
        $this->redirigerVers('/connexion');
    }
}
