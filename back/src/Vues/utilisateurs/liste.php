<?php

declare(strict_types=1);

use Wacdo\Core\Routeur;

$base = Routeur::urlBase();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Utilisateurs — Wacdo back-office</title>
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/assets/css/admin.css">
</head>
<body>
    <header class="entete-admin">
        <span class="entete-admin__marque">Wacdo — Back-office</span>
        <a href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/tableau-de-bord">← Tableau de bord</a>
    </header>

    <main class="contenu-admin">
        <h1>Gestion des utilisateurs</h1>
        <p>Module à venir (<code>EPIC 6</code>). Cette page confirme que le contrôle d'accès par rôle fonctionne : seule l'Administration peut la voir.</p>
    </main>
</body>
</html>
