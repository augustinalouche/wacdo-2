<?php

declare(strict_types=1);

namespace Wacdo\Modeles;

/**
 * Ligne d'une commande (T07.1) : soit un produit simple, soit un menu —
 * jamais les deux (cf. contrainte `chk_ligne_commande_exclusivite`, RG5).
 * La composition (accompagnement/boisson/sauce) n'existe que pour les lignes
 * de type menu.
 */
final class LigneCommande
{
    /**
     * @param array{accompagnement:string, boisson:string, sauce:string}|null $composition
     */
    public function __construct(
        public readonly int $id,
        public readonly ?int $produitId,
        public readonly ?string $produitNom,
        public readonly ?int $menuId,
        public readonly ?string $menuNom,
        public readonly ?int $tailleId,
        public readonly ?string $tailleLibelle,
        public readonly int $quantite,
        public readonly float $prixUnitaire,
        public readonly ?array $composition = null,
    ) {
    }

    public function estMenu(): bool
    {
        return $this->menuId !== null;
    }

    public function libelle(): string
    {
        $base = $this->estMenu() ? (string) $this->menuNom : (string) $this->produitNom;

        return $this->tailleLibelle !== null ? "{$base} ({$this->tailleLibelle})" : $base;
    }

    public function sousTotal(): float
    {
        return round($this->prixUnitaire * $this->quantite, 2);
    }
}
