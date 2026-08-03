<?php

declare(strict_types=1);

namespace Wacdo\Modeles;

/**
 * Entité Produit (T05.1). Les catégories `accompagnement` et `boisson` ont un
 * prix par taille (`$tailles`, table `produit_taille`) au lieu d'un prix fixe
 * (`$prix`) — jamais les deux à la fois (règle métier commune au projet).
 */
final class Produit
{
    /** @var array<int, string> catégories dont le prix dépend de la taille (Petite/Grande). */
    public const CATEGORIES_AVEC_TAILLES = ['accompagnement', 'boisson'];

    /**
     * @param array<int, array{tailleId:int, libelle:string, prix:float}> $tailles
     */
    public function __construct(
        public readonly int $id,
        public readonly string $nom,
        public readonly ?string $description,
        public readonly ?float $prix,
        public readonly int $categorieId,
        public readonly string $categorieNom,
        public readonly ?string $image,
        public readonly bool $disponible,
        public readonly array $tailles = [],
    ) {
    }

    public static function categorieUtiliseDesTailles(string $categorieNom): bool
    {
        return in_array($categorieNom, self::CATEGORIES_AVEC_TAILLES, true);
    }

    public function utiliseDesTailles(): bool
    {
        return self::categorieUtiliseDesTailles($this->categorieNom);
    }
}
