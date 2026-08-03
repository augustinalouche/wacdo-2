<?php

declare(strict_types=1);

namespace Wacdo\Core;

/**
 * Petit validateur fluide pour les formulaires du back-office (T05.9, T06.3).
 * Chaque contrôleur construit une instance, enchaîne les règles, puis teste
 * `estValide()`.
 */
final class Validation
{
    /** @var array<int, string> */
    private array $erreurs = [];

    public function requis(mixed $valeur, string $message): static
    {
        if ($valeur === null || (is_string($valeur) && trim($valeur) === '')) {
            $this->erreurs[] = $message;
        }

        return $this;
    }

    public function longueurMax(mixed $valeur, int $max, string $message): static
    {
        if (is_string($valeur) && mb_strlen($valeur) > $max) {
            $this->erreurs[] = $message;
        }

        return $this;
    }

    public function nombrePositif(mixed $valeur, string $message): static
    {
        if ($valeur !== null && (!is_numeric($valeur) || (float) $valeur < 0)) {
            $this->erreurs[] = $message;
        }

        return $this;
    }

    public function dansListe(mixed $valeur, array $liste, string $message): static
    {
        if (!in_array($valeur, $liste, true)) {
            $this->erreurs[] = $message;
        }

        return $this;
    }

    public function ajouterSi(bool $condition, string $message): static
    {
        if ($condition) {
            $this->erreurs[] = $message;
        }

        return $this;
    }

    public function estValide(): bool
    {
        return $this->erreurs === [];
    }

    /** @return array<int, string> */
    public function erreurs(): array
    {
        return $this->erreurs;
    }
}
