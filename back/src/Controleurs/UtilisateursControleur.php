<?php

declare(strict_types=1);

namespace Wacdo\Controleurs;

use Wacdo\Core\Controleur;
use Wacdo\Securite\Auth;

/**
 * Module de gestion des utilisateurs — squelette réservé au rôle
 * Administration (EPIC 6). Démontre ici le contrôle d'accès par rôle (T04.5) :
 * une Préparation ou un Accueil connecté reçoit une 403 sur cette page.
 */
final class UtilisateursControleur extends Controleur
{
    public function liste(): void
    {
        Auth::exigerModule('utilisateurs');

        $this->afficherVue('utilisateurs/liste', [
            'utilisateur' => Auth::utilisateurConnecte(),
        ]);
    }
}
