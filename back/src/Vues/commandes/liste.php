<?php

declare(strict_types=1);

use Wacdo\Core\Routeur;
use Wacdo\Securite\Csrf;

/**
 * @var array<int, \Wacdo\Modeles\Commande> $commandes
 * @var string $role
 * @var array<int, array{id:int, libelle:string}> $statuts
 * @var array{statut:string, dateDebut:string, dateFin:string} $filtres
 * @var bool $vueParDefaut
 * @var bool $peutSaisir
 * @var bool $peutPreparer
 * @var bool $peutRemettre
 * @var string|null $message
 */
$base = Routeur::urlBase();

$titre = 'Historique des commandes';
if ($vueParDefaut && $role === 'preparation') {
    $titre = 'Commandes à préparer';
} elseif ($vueParDefaut && $role === 'accueil') {
    $titre = 'Commandes prêtes à remettre';
}

$classeBadge = static function (string $statut): string {
    return match ($statut) {
        'En attente'     => 'badge-attente',
        'En preparation' => 'badge-preparation',
        'Prete'          => 'badge-prete',
        'Livree'         => 'badge-actif',
        default          => 'badge-inactif',
    };
};
$libelleStatut = static function (string $statut): string {
    return match ($statut) {
        'Prete'  => 'Prête',
        'Livree' => 'Livrée',
        default  => $statut,
    };
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Commandes — Wacdo back-office</title>
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/assets/css/admin.css">
</head>
<body>
    <header class="entete-admin">
        <span class="entete-admin__marque">Wacdo — Back-office</span>
        <a href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/tableau-de-bord">← Tableau de bord</a>
    </header>

    <main class="contenu-admin contenu-admin--large">
        <div class="barre-actions">
            <h1><?= htmlspecialchars($titre, ENT_QUOTES) ?></h1>
            <?php if ($peutSaisir): ?>
                <a class="lien-bouton" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/commandes/nouvelle">+ Nouvelle commande</a>
            <?php endif; ?>
        </div>

        <?php if ($message !== null): ?>
            <p class="message-succes"><?= htmlspecialchars($message, ENT_QUOTES) ?></p>
        <?php endif; ?>

        <form class="formulaire-filtres" method="get" action="<?= htmlspecialchars($base, ENT_QUOTES) ?>/commandes">
            <div class="champ">
                <label for="statut">Statut</label>
                <select id="statut" name="statut">
                    <option value="">Tous</option>
                    <?php foreach ($statuts as $statut): ?>
                        <option value="<?= htmlspecialchars($statut['libelle'], ENT_QUOTES) ?>" <?= $filtres['statut'] === $statut['libelle'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($libelleStatut($statut['libelle']), ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="champ">
                <label for="date_debut">Du</label>
                <input type="date" id="date_debut" name="date_debut" value="<?= htmlspecialchars($filtres['dateDebut'], ENT_QUOTES) ?>">
            </div>
            <div class="champ">
                <label for="date_fin">Au</label>
                <input type="date" id="date_fin" name="date_fin" value="<?= htmlspecialchars($filtres['dateFin'], ENT_QUOTES) ?>">
            </div>
            <button type="submit" class="bouton-discret">Filtrer</button>
            <a class="bouton-discret" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>/commandes">Réinitialiser</a>
        </form>

        <?php if ($commandes === []): ?>
            <p>Aucune commande à afficher.</p>
        <?php endif; ?>

        <div class="liste-commandes">
            <?php foreach ($commandes as $commande): ?>
                <details class="carte-commande">
                    <summary>
                        <span class="carte-commande__numero">N° <?= htmlspecialchars($commande->numeroAffichage, ENT_QUOTES) ?></span>
                        <span><?= htmlspecialchars($commande->heureAffichee(), ENT_QUOTES) ?></span>
                        <span><?= htmlspecialchars($commande->libelleOrigine(), ENT_QUOTES) ?></span>
                        <span><?= number_format($commande->montantTotal, 2, ',', ' ') ?> €</span>
                        <span class="badge <?= $classeBadge($commande->statutLibelle) ?>"><?= htmlspecialchars($libelleStatut($commande->statutLibelle), ENT_QUOTES) ?></span>
                    </summary>

                    <ul class="carte-commande__lignes">
                        <?php foreach ($commande->lignes as $ligne): ?>
                            <li>
                                <?= $ligne->quantite ?> × <?= htmlspecialchars($ligne->libelle(), ENT_QUOTES) ?>
                                — <?= number_format($ligne->sousTotal(), 2, ',', ' ') ?> €
                                <?php if ($ligne->composition !== null): ?>
                                    <div class="carte-commande__composition">
                                        <?= htmlspecialchars($ligne->composition['accompagnement'], ENT_QUOTES) ?>,
                                        <?= htmlspecialchars($ligne->composition['boisson'], ENT_QUOTES) ?>,
                                        sauce <?= htmlspecialchars($ligne->composition['sauce'], ENT_QUOTES) ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="actions-ligne">
                        <?php if ($peutPreparer && $commande->statutLibelle === 'En attente'): ?>
                            <form method="post" action="<?= htmlspecialchars($base, ENT_QUOTES) ?>/commandes/<?= $commande->id ?>/preparer">
                                <?= Csrf::champCache() ?>
                                <button type="submit" class="bouton-discret">Marquer préparée</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($peutRemettre && $commande->statutLibelle === 'Prete'): ?>
                            <form method="post" action="<?= htmlspecialchars($base, ENT_QUOTES) ?>/commandes/<?= $commande->id ?>/livrer">
                                <?= Csrf::champCache() ?>
                                <button type="submit" class="bouton-discret">Marquer livrée</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>
