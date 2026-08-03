<?php

declare(strict_types=1);

use Wacdo\Core\Routeur;
use Wacdo\Securite\Csrf;

/**
 * @var array<int, \Wacdo\Modeles\Produit> $produits
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
    <title>Produits — Wacdo back-office</title>
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
            <h1>Produits</h1>
            <a class="lien-bouton" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/produits/nouveau">+ Nouveau produit</a>
        </div>

        <?php if ($message !== null): ?>
            <p class="message-succes"><?= htmlspecialchars($message, ENT_QUOTES) ?></p>
        <?php endif; ?>

        <table class="tableau-admin">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Prix</th>
                    <th>Disponibilité</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produits as $produit): ?>
                    <tr>
                        <td>
                            <?php if ($produit->image !== null): ?>
                                <img src="<?= htmlspecialchars($baseFront . '/img/' . $produit->image, ENT_QUOTES) ?>" alt="<?= htmlspecialchars($produit->nom, ENT_QUOTES) ?>">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($produit->nom, ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($produit->categorieNom, ENT_QUOTES) ?></td>
                        <td>
                            <?php if ($produit->utiliseDesTailles()): ?>
                                <?php foreach ($produit->tailles as $taille): ?>
                                    <?= htmlspecialchars($taille['libelle'], ENT_QUOTES) ?> : <?= number_format($taille['prix'], 2, ',', ' ') ?> €<br>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?= $produit->prix !== null ? number_format($produit->prix, 2, ',', ' ') . ' €' : '—' ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $produit->disponible ? 'badge-actif' : 'badge-inactif' ?>">
                                <?= $produit->disponible ? 'Disponible' : 'Indisponible' ?>
                            </span>
                        </td>
                        <td class="actions-ligne">
                            <a class="bouton-discret" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/produits/<?= $produit->id ?>/editer">Éditer</a>
                            <form method="post" action="<?= htmlspecialchars($base, ENT_QUOTES) ?>/produits/<?= $produit->id ?>/disponibilite">
                                <?= Csrf::champCache() ?>
                                <button type="submit" class="bouton-discret"><?= $produit->disponible ? 'Désactiver' : 'Activer' ?></button>
                            </form>
                            <form method="post" action="<?= htmlspecialchars($base, ENT_QUOTES) ?>/produits/<?= $produit->id ?>/supprimer" onsubmit="return confirm('Supprimer ce produit ?');">
                                <?= Csrf::champCache() ?>
                                <button type="submit" class="bouton-discret bouton-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($produits === []): ?>
                    <tr><td colspan="6">Aucun produit pour le moment.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
