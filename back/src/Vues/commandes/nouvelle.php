<?php

declare(strict_types=1);

use Wacdo\Core\Routeur;
use Wacdo\Securite\Csrf;

/**
 * @var array<int, \Wacdo\Modeles\Produit> $produitsSansTaille
 * @var array<int, \Wacdo\Modeles\Produit> $produitsAvecTaille
 * @var array<int, \Wacdo\Modeles\Menu> $menus
 * @var array<int, array{id:int, libelle:string, supplement:float}> $tailles
 * @var array<int, array{id:int, nom:string}> $sauces
 * @var array<int, string> $erreurs
 * @var array<string, mixed> $valeursSoumises
 */
$base = Routeur::urlBase();

$quantiteProduit = static fn (int $produitId): string => (string) ($valeursSoumises['produits'][$produitId] ?? '');
$quantiteProduitTaille = static fn (int $produitId, int $tailleId): string =>
    (string) ($valeursSoumises['produitsTaille'][$produitId][$tailleId] ?? '');
$quantiteMenu = static fn (int $menuId): string => (string) ($valeursSoumises['menus'][$menuId] ?? '');
$compositionMenu = static fn (int $menuId, string $champ): string =>
    (string) ($valeursSoumises['menuComposition'][$menuId][$champ] ?? '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nouvelle commande — Wacdo back-office</title>
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/assets/css/admin.css">
</head>
<body>
    <header class="entete-admin">
        <span class="entete-admin__marque">Wacdo — Back-office</span>
        <a href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/tableau-de-bord">← Tableau de bord</a>
    </header>

    <main class="contenu-admin contenu-admin--large">
        <a class="retour-liste" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/commandes">← Retour aux commandes</a>
        <h1>Nouvelle commande (comptoir / téléphone)</h1>

        <?php if ($erreurs !== []): ?>
            <ul class="liste-erreurs message-erreur" role="alert">
                <?php foreach ($erreurs as $erreur): ?>
                    <li><?= htmlspecialchars($erreur, ENT_QUOTES) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form class="formulaire-admin" method="post" action="<?= htmlspecialchars($base, ENT_QUOTES) ?>/commandes">
            <?= Csrf::champCache() ?>

            <div class="ligne-deux-colonnes">
                <div class="champ">
                    <label for="numero">Numéro de récupération</label>
                    <input type="text" id="numero" name="numero" maxlength="20" required
                           value="<?= htmlspecialchars((string) ($valeursSoumises['numero'] ?? ''), ENT_QUOTES) ?>">
                </div>
                <div class="champ">
                    <label for="origine">Origine</label>
                    <select id="origine" name="origine" required>
                        <?php $origineSoumise = (string) ($valeursSoumises['origine'] ?? 'comptoir'); ?>
                        <option value="comptoir" <?= $origineSoumise === 'comptoir' ? 'selected' : '' ?>>Comptoir</option>
                        <option value="telephone" <?= $origineSoumise === 'telephone' ? 'selected' : '' ?>>Téléphone</option>
                    </select>
                </div>
            </div>

            <fieldset>
                <legend>Produits</legend>
                <table class="tableau-admin">
                    <thead>
                        <tr><th>Produit</th><th>Prix</th><th>Quantité</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produitsSansTaille as $produit): ?>
                            <tr>
                                <td><?= htmlspecialchars($produit->nom, ENT_QUOTES) ?></td>
                                <td><?= number_format((float) $produit->prix, 2, ',', ' ') ?> €</td>
                                <td>
                                    <input type="number" min="0" max="20" step="1"
                                           name="produits[<?= $produit->id ?>]"
                                           value="<?= htmlspecialchars($quantiteProduit($produit->id), ENT_QUOTES) ?>"
                                           class="champ-quantite">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php foreach ($produitsAvecTaille as $produit): ?>
                            <?php foreach ($produit->tailles as $taille): ?>
                                <tr>
                                    <td><?= htmlspecialchars($produit->nom, ENT_QUOTES) ?> (<?= htmlspecialchars($taille['libelle'], ENT_QUOTES) ?>)</td>
                                    <td><?= number_format($taille['prix'], 2, ',', ' ') ?> €</td>
                                    <td>
                                        <input type="number" min="0" max="20" step="1"
                                               name="produitsTaille[<?= $produit->id ?>][<?= $taille['tailleId'] ?>]"
                                               value="<?= htmlspecialchars($quantiteProduitTaille($produit->id, $taille['tailleId']), ENT_QUOTES) ?>"
                                               class="champ-quantite">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </fieldset>

            <fieldset>
                <legend>Menus</legend>
                <?php foreach ($menus as $menu): ?>
                    <div class="bloc-menu">
                        <div class="bloc-menu__entete">
                            <strong><?= htmlspecialchars($menu->nom, ENT_QUOTES) ?></strong> (<?= number_format($menu->prixBase, 2, ',', ' ') ?> € de base)
                            <label>
                                Quantité
                                <input type="number" min="0" max="20" step="1"
                                       name="menus[<?= $menu->id ?>]"
                                       value="<?= htmlspecialchars($quantiteMenu($menu->id), ENT_QUOTES) ?>"
                                       class="champ-quantite">
                            </label>
                        </div>
                        <div class="bloc-menu__composition">
                            <div class="champ">
                                <label for="accompagnement-<?= $menu->id ?>">Accompagnement</label>
                                <select id="accompagnement-<?= $menu->id ?>" name="menuComposition[<?= $menu->id ?>][accompagnementProduitId]">
                                    <option value="">—</option>
                                    <?php foreach ($produitsAvecTaille as $produit): ?>
                                        <?php if ($produit->categorieNom !== 'accompagnement') { continue; } ?>
                                        <option value="<?= $produit->id ?>" <?= $compositionMenu($menu->id, 'accompagnementProduitId') === (string) $produit->id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($produit->nom, ENT_QUOTES) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="champ">
                                <label for="accompagnement-taille-<?= $menu->id ?>">Taille</label>
                                <select id="accompagnement-taille-<?= $menu->id ?>" name="menuComposition[<?= $menu->id ?>][accompagnementTailleId]">
                                    <?php foreach ($tailles as $taille): ?>
                                        <option value="<?= $taille['id'] ?>" <?= $compositionMenu($menu->id, 'accompagnementTailleId') === (string) $taille['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($taille['libelle'], ENT_QUOTES) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="champ">
                                <label for="boisson-<?= $menu->id ?>">Boisson</label>
                                <select id="boisson-<?= $menu->id ?>" name="menuComposition[<?= $menu->id ?>][boissonProduitId]">
                                    <option value="">—</option>
                                    <?php foreach ($produitsAvecTaille as $produit): ?>
                                        <?php if ($produit->categorieNom !== 'boisson') { continue; } ?>
                                        <option value="<?= $produit->id ?>" <?= $compositionMenu($menu->id, 'boissonProduitId') === (string) $produit->id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($produit->nom, ENT_QUOTES) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="champ">
                                <label for="boisson-taille-<?= $menu->id ?>">Taille</label>
                                <select id="boisson-taille-<?= $menu->id ?>" name="menuComposition[<?= $menu->id ?>][boissonTailleId]">
                                    <?php foreach ($tailles as $taille): ?>
                                        <option value="<?= $taille['id'] ?>" <?= $compositionMenu($menu->id, 'boissonTailleId') === (string) $taille['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($taille['libelle'], ENT_QUOTES) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="champ">
                                <label for="sauce-<?= $menu->id ?>">Sauce</label>
                                <select id="sauce-<?= $menu->id ?>" name="menuComposition[<?= $menu->id ?>][sauceId]">
                                    <option value="">—</option>
                                    <?php foreach ($sauces as $sauce): ?>
                                        <option value="<?= $sauce['id'] ?>" <?= $compositionMenu($menu->id, 'sauceId') === (string) $sauce['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($sauce['nom'], ENT_QUOTES) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <p class="comptes-test-demo">La composition (accompagnement/boisson/sauce) n'est requise que pour les menus dont la quantité est supérieure à 0.</p>
            </fieldset>

            <button type="submit" class="btn">Enregistrer la commande</button>
        </form>
    </main>
</body>
</html>
