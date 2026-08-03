<?php

declare(strict_types=1);

namespace Wacdo\Modeles;

/**
 * Rôle Administration : accès complet au back-office (T04.1).
 */
final class Administrateur extends Utilisateur
{
    public function role(): string
    {
        return 'administration';
    }

    public function libelleRole(): string
    {
        return 'Administration';
    }

    public function modulesAutorises(): array
    {
        return ['utilisateurs', 'produits', 'menus', 'commandes', 'statistiques'];
    }
}
