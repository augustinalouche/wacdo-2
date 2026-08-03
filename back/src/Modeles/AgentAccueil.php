<?php

declare(strict_types=1);

namespace Wacdo\Modeles;

/**
 * Rôle Accueil : saisie des commandes au comptoir et remise au client (T04.1).
 */
final class AgentAccueil extends Utilisateur
{
    public function role(): string
    {
        return 'accueil';
    }

    public function libelleRole(): string
    {
        return 'Accueil';
    }

    public function modulesAutorises(): array
    {
        return ['commandes'];
    }
}
