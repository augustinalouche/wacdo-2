<?php

declare(strict_types=1);

use Wacdo\Core\Routeur;
use Wacdo\Securite\Csrf;

/**
 * @var array<int, \Wacdo\Modeles\Utilisateur> $utilisateurs
 * @var array{id:int, nom:string, prenom:string, email:string, role:string, libelleRole:string} $utilisateurConnecte
 * @var string|null $message
 */
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
        <div class="barre-actions">
            <h1>Utilisateurs</h1>
            <a class="lien-bouton" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/utilisateurs/nouveau">+ Nouvel utilisateur</a>
        </div>

        <?php if ($message !== null): ?>
            <p class="message-succes"><?= htmlspecialchars($message, ENT_QUOTES) ?></p>
        <?php endif; ?>

        <table class="tableau-admin">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($utilisateurs as $utilisateur): ?>
                    <?php $estSoiMeme = $utilisateur->id() === $utilisateurConnecte['id']; ?>
                    <tr>
                        <td><?= htmlspecialchars($utilisateur->nomComplet(), ENT_QUOTES) ?><?= $estSoiMeme ? ' <em>(vous)</em>' : '' ?></td>
                        <td><?= htmlspecialchars($utilisateur->email(), ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($utilisateur->libelleRole(), ENT_QUOTES) ?></td>
                        <td>
                            <span class="badge <?= $utilisateur->estActif() ? 'badge-actif' : 'badge-inactif' ?>">
                                <?= $utilisateur->estActif() ? 'Actif' : 'Désactivé' ?>
                            </span>
                        </td>
                        <td class="actions-ligne">
                            <a class="bouton-discret" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/utilisateurs/<?= $utilisateur->id() ?>/editer">Éditer</a>
                            <?php if (!$estSoiMeme): ?>
                                <form method="post" action="<?= htmlspecialchars($base, ENT_QUOTES) ?>/utilisateurs/<?= $utilisateur->id() ?>/statut">
                                    <?= Csrf::champCache() ?>
                                    <button type="submit" class="bouton-discret"><?= $utilisateur->estActif() ? 'Désactiver' : 'Activer' ?></button>
                                </form>
                                <form method="post" action="<?= htmlspecialchars($base, ENT_QUOTES) ?>/utilisateurs/<?= $utilisateur->id() ?>/supprimer" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                    <?= Csrf::champCache() ?>
                                    <button type="submit" class="bouton-discret bouton-danger">Supprimer</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
