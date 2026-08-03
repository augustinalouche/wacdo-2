<?php

declare(strict_types=1);

namespace Wacdo\Modeles;

/**
 * Commande (T07.1) : en-tête + lignes associées. Le statut évolue via
 * `CommandeDepot::changerStatut()` selon le rôle (Préparation/Accueil).
 */
final class Commande
{
    /** @param array<int, LigneCommande> $lignes */
    public function __construct(
        public readonly int $id,
        public readonly string $numeroAffichage,
        public readonly string $dateHeure,
        public readonly string $statutLibelle,
        public readonly float $montantTotal,
        public readonly string $origine,
        public readonly ?int $utilisateurId,
        public readonly array $lignes = [],
    ) {
    }

    public function heureAffichee(): string
    {
        return (new \DateTimeImmutable($this->dateHeure))->format('d/m/Y H:i');
    }

    public function libelleOrigine(): string
    {
        return match ($this->origine) {
            'borne'     => 'Borne',
            'comptoir'  => 'Comptoir',
            'telephone' => 'Téléphone',
            default     => $this->origine,
        };
    }
}
