<?php

declare(strict_types=1);

use Wacdo\Core\Routeur;

/** @var array{id:int, nom:string, prenom:string, email:string, role:string, libelleRole:string} $utilisateur */
$base = Routeur::urlBase();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tableau de bord — Wacdo back-office</title>
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/assets/css/admin.css">
</head>
<body>
    <header class="entete-admin">
        <span class="entete-admin__marque">Wacdo — Back-office</span>
        <form method="post" action="<?= htmlspecialchars($base, ENT_QUOTES) ?>/deconnexion">
            <button type="submit" class="btn" style="width:auto; padding: 8px 16px;">Se déconnecter</button>
        </form>
    </header>

    <main class="contenu-admin">
        <h1>Bienvenue, <?= htmlspecialchars($utilisateur['prenom'], ENT_QUOTES) ?> !</h1>
        <p>Connecté(e) en tant que <strong><?= htmlspecialchars($utilisateur['libelleRole'], ENT_QUOTES) ?></strong> (<?= htmlspecialchars($utilisateur['email'], ENT_QUOTES) ?>).</p>

        <ul>
            <li><a href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/produits">Produits</a></li>
            <li><a href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/menus">Menus</a></li>
            <li><a href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/utilisateurs">Utilisateurs</a> (réservé à l'Administration)</li>
        </ul>
        <p>Le module « Commandes » (rôles Préparation/Accueil) sera ajouté avec <code>EPIC 7</code>.</p>
    </main>
</body>
</html>
