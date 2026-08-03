<?php

declare(strict_types=1);

use Wacdo\Core\Routeur;
use Wacdo\Modeles\Produit;
use Wacdo\Securite\Csrf;

/**
 * @var Produit|null $produit
 * @var array<int, array{id:int, nom:string}> $categories
 * @var array<int, array{id:int, libelle:string, supplement:float}> $tailles
 * @var array<int, string> $erreurs
 * @var array<string, mixed> $valeursSoumises
 */
$base = Routeur::urlBase();
$baseFront = preg_replace('#/back$#', '/front', $base);
$modeEdition = $produit !== null;

$valeur = static function (string $cle, mixed $defaut = '') use ($valeursSoumises, $produit): mixed {
    if (array_key_exists($cle, $valeursSoumises)) {
        return $valeursSoumises[$cle];
    }

    return match ($cle) {
        'nom'          => $produit?->nom ?? $defaut,
        'description'  => $produit?->description ?? $defaut,
        'categorie_id' => $produit?->categorieId ?? $defaut,
        'prix'         => $produit?->prix ?? $defaut,
        default        => $defaut,
    };
};

$categorieIdActuelle = (int) $valeur('categorie_id', 0);
$utiliseDesTaillesInitialement = $produit?->utiliseDesTailles() ?? false;

// Petite = index 0, Grande = index 1 (voir back/sql/seed.sql).
$tailleIdPetite = $tailles[0]['id'] ?? 0;
$tailleIdGrande = $tailles[1]['id'] ?? 0;
$prixPetiteActuel = '';
$prixGrandeActuel = '';
if ($produit !== null) {
    foreach ($produit->tailles as $t) {
        if ($t['tailleId'] === $tailleIdPetite) {
            $prixPetiteActuel = (string) $t['prix'];
        }
        if ($t['tailleId'] === $tailleIdGrande) {
            $prixGrandeActuel = (string) $t['prix'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $modeEdition ? 'Modifier' : 'Nouveau' ?> produit — Wacdo back-office</title>
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/assets/css/admin.css">
</head>
<body>
    <header class="entete-admin">
        <span class="entete-admin__marque">Wacdo — Back-office</span>
        <a href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/tableau-de-bord">← Tableau de bord</a>
    </header>

    <main class="contenu-admin">
        <a class="retour-liste" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/produits">← Retour aux produits</a>
        <h1><?= $modeEdition ? 'Modifier le produit' : 'Nouveau produit' ?></h1>

        <?php if ($erreurs !== []): ?>
            <ul class="liste-erreurs message-erreur" role="alert">
                <?php foreach ($erreurs as $erreur): ?>
                    <li><?= htmlspecialchars($erreur, ENT_QUOTES) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form class="formulaire-admin" method="post"
              action="<?= htmlspecialchars($base, ENT_QUOTES) ?>/produits<?= $modeEdition ? '/' . $produit->id : '' ?>"
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
                <label for="categorie_id">Catégorie</label>
                <select id="categorie_id" name="categorie_id" required onchange="wacdoBasculerTailles(this)">
                    <option value="">— Choisir —</option>
                    <?php foreach ($categories as $categorie): ?>
                        <option
                            value="<?= $categorie['id'] ?>"
                            data-utilise-tailles="<?= Produit::categorieUtiliseDesTailles($categorie['nom']) ? '1' : '0' ?>"
                            <?= $categorieIdActuelle === $categorie['id'] ? 'selected' : '' ?>
                        ><?= htmlspecialchars(ucfirst($categorie['nom']), ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="champ" id="section-prix-simple">
                <label for="prix">Prix (€)</label>
                <input type="number" id="prix" name="prix" step="0.01" min="0" value="<?= htmlspecialchars((string) $valeur('prix'), ENT_QUOTES) ?>">
            </div>

            <fieldset id="section-prix-tailles">
                <legend>Prix par taille (€)</legend>
                <input type="hidden" name="taille_id_petite" value="<?= $tailleIdPetite ?>">
                <input type="hidden" name="taille_id_grande" value="<?= $tailleIdGrande ?>">
                <div class="ligne-deux-colonnes">
                    <div class="champ">
                        <label for="prix_petite">Petite</label>
                        <input type="number" id="prix_petite" name="prix_petite" step="0.01" min="0" value="<?= htmlspecialchars((string) ($valeursSoumises['prix_petite'] ?? $prixPetiteActuel), ENT_QUOTES) ?>">
                    </div>
                    <div class="champ">
                        <label for="prix_grande">Grande (+<?= number_format($tailles[1]['supplement'] ?? 0.5, 2, ',', ' ') ?> € en général)</label>
                        <input type="number" id="prix_grande" name="prix_grande" step="0.01" min="0" value="<?= htmlspecialchars((string) ($valeursSoumises['prix_grande'] ?? $prixGrandeActuel), ENT_QUOTES) ?>">
                    </div>
                </div>
            </fieldset>

            <div class="champ apercu-image">
                <label>Image</label>
                <?php if ($produit?->image !== null): ?>
                    <img src="<?= htmlspecialchars($baseFront . '/img/' . $produit->image, ENT_QUOTES) ?>" alt="Image actuelle de <?= htmlspecialchars($produit->nom, ENT_QUOTES) ?>">
                <?php endif; ?>
                <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp">
            </div>

            <div class="champ champ-checkbox" style="flex-direction: row;">
                <input type="checkbox" id="disponible" name="disponible" value="1" <?= ($valeursSoumises['disponible'] ?? ($produit?->disponible ? '1' : '')) === '1' ? 'checked' : '' ?>>
                <label for="disponible">Disponible à la vente</label>
            </div>

            <button type="submit" class="btn"><?= $modeEdition ? 'Enregistrer les modifications' : 'Créer le produit' ?></button>
        </form>
    </main>

    <script>
        function wacdoBasculerTailles(select) {
            var option = select.options[select.selectedIndex];
            var utiliseTailles = option && option.dataset.utiliseTailles === '1';
            document.getElementById('section-prix-simple').style.display = utiliseTailles ? 'none' : '';
            document.getElementById('section-prix-tailles').style.display = utiliseTailles ? '' : 'none';
        }
        wacdoBasculerTailles(document.getElementById('categorie_id'));
    </script>
</body>
</html>
