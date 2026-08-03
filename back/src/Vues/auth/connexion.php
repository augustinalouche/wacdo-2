<?php

declare(strict_types=1);

use Wacdo\Core\Routeur;
use Wacdo\Securite\Csrf;

/** @var string|null $erreur */
$base = Routeur::urlBase();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — Wacdo back-office</title>
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/assets/css/admin.css">
</head>
<body>
    <main class="page-connexion">
        <form class="carte-connexion" method="post" action="<?= htmlspecialchars($base, ENT_QUOTES) ?>/connexion" novalidate>
            <h1>Wacdo — Back-office</h1>

            <?php if ($erreur !== null): ?>
                <p class="message-erreur" role="alert"><?= htmlspecialchars($erreur, ENT_QUOTES) ?></p>
            <?php endif; ?>

            <?= Csrf::champCache() ?>

            <div class="champ">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autocomplete="username" autofocus>
            </div>

            <div class="champ">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn">Se connecter</button>

            <p class="comptes-test-demo">
                Comptes de test (voir <code>back/sql/seed.sql</code>), mot de passe <code>Wacdo2026!</code> :
                admin@wacdo.test · preparation@wacdo.test · accueil@wacdo.test
            </p>
        </form>
    </main>
</body>
</html>
