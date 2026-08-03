<?php

declare(strict_types=1);

namespace Wacdo\Modeles;

/**
 * Entité Menu (T05.6) : toujours associé à un burger unique (contrainte
 * `uq_menu_burger`) ; l'accompagnement, la boisson et la sauce sont choisis
 * librement par le client parmi les produits/sauces disponibles (pas de
 * liste figée par menu — règle métier commune, voir `docs/conception/04-structure-json.md`).
 */
final class Menu
{
    public function __construct(
        public readonly int $id,
        public readonly string $nom,
        public readonly ?string $description,
        public readonly float $prixBase,
        public readonly int $burgerId,
        public readonly string $burgerNom,
        public readonly ?string $image,
        public readonly bool $disponible,
    ) {
    }
}
