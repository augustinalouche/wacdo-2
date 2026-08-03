<?php
/**
 * Modele de configuration. Copier ce fichier en "config.php" (meme dossier)
 * et renseigner les vraies valeurs. "config.php" est ignore par Git
 * (voir .gitignore a la racine) : il ne doit JAMAIS etre commite.
 */

return [
    'db' => [
        'driver'   => 'mysql',
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'wacdo2',
        'charset'  => 'utf8mb4',
        'user'     => 'root',
        'password' => '',
    ],

    // 'dev' affiche les erreurs detaillees, 'prod' les masque derriere une page 500.
    'environnement' => 'dev',

    'session' => [
        'nom' => 'wacdo_session',
        'duree_minutes' => 120,
    ],

    // Origine autorisee a appeler l'API ("/api/*") en cross-origin (T08.5).
    // "*" en developpement ; a restreindre a l'URL exacte du front en production
    // (ex. "https://wacdo-front.exemple.com").
    'cors' => [
        'origine_autorisee' => '*',
    ],
];
