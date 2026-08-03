<?php

declare(strict_types=1);

namespace Wacdo\Controleurs\Api;

use Wacdo\Modeles\Menu;
use Wacdo\Modeles\MenuDepot;
use Wacdo\Modeles\ReferentielDepot;

/**
 * `GET /api/menus` (T08.2) — même structure que `front/data/menus.json`
 * (voir `docs/conception/04-structure-json.md`) : sauces disponibles +
 * menus (la composition détaillée reste au choix du client, jamais figée
 * par menu — règle métier commune à `EPIC 5`/`EPIC 7`).
 */
final class MenusApiControleur extends ApiControleur
{
    public function index(): void
    {
        $menus = array_map($this->versJson(...), (new MenuDepot())->trouverTous());
        $sauces = array_map(
            static fn (array $sauce) => ['id' => $sauce['id'], 'nom' => $sauce['nom']],
            (new ReferentielDepot())->sauces()
        );

        $this->reussite([
            'sauces' => $sauces,
            'menus'  => $menus,
        ]);
    }

    /** @return array<string, mixed> */
    private function versJson(Menu $menu): array
    {
        return [
            'id'          => $menu->id,
            'nom'         => $menu->nom,
            'description' => $menu->description,
            'prixBase'    => $menu->prixBase,
            'burgerId'    => $menu->burgerId,
            'image'       => $menu->image,
            'disponible'  => $menu->disponible,
        ];
    }
}
