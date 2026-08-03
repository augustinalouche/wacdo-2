<?php

declare(strict_types=1);

use Wacdo\Core\Routeur;
use Wacdo\Securite\Auth;
use Wacdo\Securite\Csrf;

/**
 * @var \Wacdo\Modeles\Utilisateur|null $utilisateur
 * @var array<string, string> $roles
 * @var array<int, string> $erreurs
 * @var array<string, mixed> $valeursSoumises
 */
$base = Routeur::urlBase();
$modeEdition = $utilisateur !== null;
$estSoiMeme = $modeEdition && $utilisateur->id() === (int) (Auth::utilisateurConnecte()['id'] ?? 0);

$valeur = static function (string $cle, mixed $defaut = '') use ($valeursSoumises, $utilisateur): mixed {
    if (array_key_exists($cle, $valeursSoumises)) {
        return $valeursSoumises[$cle];
    }

    return match ($cle) {
        'nom'    => $utilisateur?->nom() ?? $defaut,
        'prenom' => $utilisateur?->prenom() ?? $defaut,
        'email'  => $utilisateur?->email() ?? $defaut,
        'role'   => $utilisateur?->role() ?? $defaut,
        default  => $defaut,
    };
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $modeEdition ? 'Modifier' : 'Nouvel' ?> utilisateur — Wacdo back-office</title>
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/assets/css/admin.css">
</head>
<body>
    <header class="entete-admin">
        <span class="entete-admin__marque">Wacdo — Back-office</span>
        <a href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/tableau-de-bord">← Tableau de bord</a>
    </header>

    <main class="contenu-admin">
        <a class="retour-liste" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/utilisateurs">← Retour aux utilisateurs</a>
        <h1><?= $modeEdition ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' ?></h1>

        <?php if ($erreurs !== []): ?>
            <ul class="liste-erreurs message-erreur" role="alert">
                <?php foreach ($erreurs as $erreur): ?>
                    <li><?= htmlspecialchars($erreur, ENT_QUOTES) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form class="formulaire-admin" method="post"
              action="<?= htmlspecialchars($base, ENT_QUOTES) ?>/utilisateurs<?= $modeEdition ? '/' . $utilisateur->id() : '' ?>"
              novalidate>
            <?= Csrf::champCache() ?>

            <div class="ligne-deux-colonnes">
                <div class="champ">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" maxlength="50" required value="<?= htmlspecialchars((string) $valeur('prenom'), ENT_QUOTES) ?>">
                </div>
                <div class="champ">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" maxlength="50" required value="<?= htmlspecialchars((string) $valeur('nom'), ENT_QUOTES) ?>">
                </div>
            </div>

            <div class="champ">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" maxlength="150" required value="<?= htmlspecialchars((string) $valeur('email'), ENT_QUOTES) ?>">
            </div>

            <div class="champ">
                <label for="role">Rôle</label>
                <select id="role" name="role" required <?= $estSoiMeme ? 'disabled' : '' ?>>
                    <?php foreach ($roles as $valeurRole => $libelle): ?>
                        <option value="<?= htmlspecialchars($valeurRole, ENT_QUOTES) ?>" <?= $valeur('role') === $valeurRole ? 'selected' : '' ?>>
                            <?= htmlspecialchars($libelle, ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($estSoiMeme): ?>
                    <input type="hidden" name="role" value="<?= htmlspecialchars((string) $valeur('role'), ENT_QUOTES) ?>">
                    <p class="comptes-test-demo">Vous ne pouvez pas changer votre propre rôle.</p>
                <?php endif; ?>
            </div>

            <div class="champ">
                <label for="mot_de_passe">Mot de passe <?= $modeEdition ? '(laisser vide pour ne pas le modifier)' : '' ?></label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" autocomplete="new-password" minlength="8" <?= $modeEdition ? '' : 'required' ?>>
            </div>

            <div class="champ champ-checkbox" style="flex-direction: row;">
                <input type="checkbox" id="actif" name="actif" value="1"
                    <?= ($valeursSoumises['actif'] ?? ($utilisateur?->estActif() !== false ? '1' : '')) === '1' ? 'checked' : '' ?>
                    <?= $estSoiMeme ? 'disabled' : '' ?>>
                <label for="actif">Compte actif</label>
                <?php if ($estSoiMeme): ?>
                    <input type="hidden" name="actif" value="1">
                <?php endif; ?>
            </div>

            <button type="submit" class="btn"><?= $modeEdition ? 'Enregistrer les modifications' : 'Créer l\'utilisateur' ?></button>
        </form>
    </main>
</body>
</html>
