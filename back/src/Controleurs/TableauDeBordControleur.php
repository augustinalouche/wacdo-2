<?php

declare(strict_types=1);

namespace Wacdo\Controleurs;

use Wacdo\Core\Controleur;
use Wacdo\Securite\Auth;

/**
 * Tableau de bord affiché après connexion (démonstration de T04.5 : accessible
 * à tout utilisateur connecté, quel que soit son rôle).
 */
final class TableauDeBordControleur extends Controleur
{
    public function afficher(): void
    {
        Auth::exigerConnexion();

        $this->afficherVue('tableau-de-bord', [
            'utilisateur' => Auth::utilisateurConnecte(),
        ]);
    }
}
