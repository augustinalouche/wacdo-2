<?php

declare(strict_types=1);

namespace Wacdo\Modeles;

/**
 * Rôle Préparation : suivi des commandes à préparer (T04.1).
 */
final class Preparateur extends Utilisateur
{
    public function role(): string
    {
        return 'preparation';
    }

    public function libelleRole(): string
    {
        return 'Préparation';
    }

    public function modulesAutorises(): array
    {
        return ['commandes'];
    }
}
