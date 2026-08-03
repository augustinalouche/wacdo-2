<?php

declare(strict_types=1);

use Wacdo\Core\Routeur;
use Wacdo\Securite\Csrf;

/**
 * @var array<int, \Wacdo\Modeles\Menu> $menus
 * @var string|null $message
 */
$base = Routeur::urlBase();
$baseFront = preg_replace('#/back$#', '/front', $base);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Menus — Wacdo back-office</title>
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/assets/css/admin.css">
</head>
<body>
    <header class="entete-admin">
        <span class="entete-admin__marque">Wacdo — Back-office</span>
        <a href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/tableau-de-bord">← Tableau de bord</a>
    </header>

    <main class="contenu-admin">
        <div class="barre-actions">
            <h1>Menus</h1>
            <a class="lien-bouton" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/menus/nouveau">+ Nouveau menu</a>
        </div>

        <?php if ($message !== null): ?>
            <p class="message-succes"><?= htmlspecialchars($message, ENT_QUOTES) ?></p>
        <?php endif; ?>

        <table class="tableau-admin">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Nom</th>
                    <th>Burger</th>
                    <th>Prix de base</th>
                    <th>Disponibilité</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($menus as $menu): ?>
                    <tr>
                        <td>
                            <?php if ($menu->image !== null): ?>
                                <img src="<?= htmlspecialchars($baseFront . '/img/' . $menu->image, ENT_QUOTES) ?>" alt="<?= htmlspecialchars($menu->nom, ENT_QUOTES) ?>">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($menu->nom, ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($menu->burgerNom, ENT_QUOTES) ?></td>
                        <td><?= number_format($menu->prixBase, 2, ',', ' ') ?> €</td>
                        <td>
                            <span class="badge <?= $menu->disponible ? 'badge-actif' : 'badge-inactif' ?>">
                                <?= $menu->disponible ? 'Disponible' : 'Indisponible' ?>
                            </span>
                        </td>
                        <td class="actions-ligne">
                            <a class="bouton-discret" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/menus/<?= $menu->id ?>/editer">Éditer</a>
                            <form method="post" action="<?= htmlspecialchars($base, ENT_QUOTES) ?>/menus/<?= $menu->id ?>/supprimer" onsubmit="return confirm('Supprimer ce menu ?');">
                                <?= Csrf::champCache() ?>
                                <button type="submit" class="bouton-discret bouton-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($menus === []): ?>
                    <tr><td colspan="6">Aucun menu pour le moment.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
