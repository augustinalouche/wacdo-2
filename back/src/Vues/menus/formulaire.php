<?php

declare(strict_types=1);

use Wacdo\Core\Routeur;
use Wacdo\Securite\Csrf;

/**
 * @var \Wacdo\Modeles\Menu|null $menu
 * @var array<int, array{id:int, nom:string}> $burgersDisponibles
 * @var array<int, string> $erreurs
 * @var array<string, mixed> $valeursSoumises
 */
$base = Routeur::urlBase();
$baseFront = preg_replace('#/back$#', '/front', $base);
$modeEdition = $menu !== null;

$valeur = static function (string $cle, mixed $defaut = '') use ($valeursSoumises, $menu): mixed {
    if (array_key_exists($cle, $valeursSoumises)) {
        return $valeursSoumises[$cle];
    }

    return match ($cle) {
        'nom'         => $menu?->nom ?? $defaut,
        'description' => $menu?->description ?? $defaut,
        'prix_base'   => $menu?->prixBase ?? $defaut,
        'burger_id'   => $menu?->burgerId ?? $defaut,
        default       => $defaut,
    };
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $modeEdition ? 'Modifier' : 'Nouveau' ?> menu — Wacdo back-office</title>
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/assets/css/admin.css">
</head>
<body>
    <header class="entete-admin">
        <span class="entete-admin__marque">Wacdo — Back-office</span>
        <a href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/tableau-de-bord">← Tableau de bord</a>
    </header>

    <main class="contenu-admin">
        <a class="retour-liste" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/menus">← Retour aux menus</a>
        <h1><?= $modeEdition ? 'Modifier le menu' : 'Nouveau menu' ?></h1>

        <?php if ($erreurs !== []): ?>
            <ul class="liste-erreurs message-erreur" role="alert">
                <?php foreach ($erreurs as $erreur): ?>
                    <li><?= htmlspecialchars($erreur, ENT_QUOTES) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($burgersDisponibles === [] && !$modeEdition): ?>
            <p class="message-erreur">Tous les burgers ont déjà un menu associé. Créez d'abord un nouveau burger dans les produits.</p>
        <?php endif; ?>

        <form class="formulaire-admin" method="post"
              action="<?= htmlspecialchars($base, ENT_QUOTES) ?>/menus<?= $modeEdition ? '/' . $menu->id : '' ?>"
              enctype="multipart/form-data" novalidate>
            <?= Csrf::champCache() ?>

            <div class="champ">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" maxlength="100" required value="<?= htmlspecialchars((string) $valeur('nom'), ENT_QUOTES) ?>">
            </div>

            <div class="champ">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"><?= htmlspecialchars((string) $valeur('description'), ENT_QUOTES) ?></textarea>
            </div>

            <div class="champ">
                <label for="burger_id">Burger associé</label>
                <select id="burger_id" name="burger_id" required>
                    <option value="">— Choisir —</option>
                    <?php foreach ($burgersDisponibles as $burger): ?>
                        <option value="<?= $burger['id'] ?>" <?= (int) $valeur('burger_id') === $burger['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($burger['nom'], ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="champ">
                <label for="prix_base">Prix de base (€)</label>
                <input type="number" id="prix_base" name="prix_base" step="0.01" min="0" value="<?= htmlspecialchars((string) $valeur('prix_base'), ENT_QUOTES) ?>">
            </div>

            <div class="champ apercu-image">
                <label>Image</label>
                <?php if ($menu?->image !== null): ?>
                    <img src="<?= htmlspecialchars($baseFront . '/img/' . $menu->image, ENT_QUOTES) ?>" alt="Image actuelle de <?= htmlspecialchars($menu->nom, ENT_QUOTES) ?>">
                <?php endif; ?>
                <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp">
            </div>

            <div class="champ champ-checkbox" style="flex-direction: row;">
                <input type="checkbox" id="disponible" name="disponible" value="1" <?= ($valeursSoumises['disponible'] ?? ($menu?->disponible ? '1' : '')) === '1' ? 'checked' : '' ?>>
                <label for="disponible">Disponible à la vente</label>
            </div>

            <button type="submit" class="btn"><?= $modeEdition ? 'Enregistrer les modifications' : 'Créer le menu' ?></button>
        </form>
    </main>
</body>
</html>
