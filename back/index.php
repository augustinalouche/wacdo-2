<?php

declare(strict_types=1);

use Wacdo\Controleurs\AuthControleur;
use Wacdo\Controleurs\TableauDeBordControleur;
use Wacdo\Controleurs\UtilisateursControleur;
use Wacdo\Core\Autoloader;
use Wacdo\Core\Routeur;

require __DIR__ . '/src/Core/Autoloader.php';

Autoloader::register();
Autoloader::ajouterEspaceDeNoms('Wacdo', __DIR__ . '/src');

$config = require __DIR__ . '/config/config.php';

$modeDev = ($config['environnement'] ?? 'prod') === 'dev';
error_reporting($modeDev ? E_ALL : 0);
ini_set('display_errors', $modeDev ? '1' : '0');

session_name($config['session']['nom'] ?? 'wacdo_session');
session_start();

set_exception_handler(static function (\Throwable $exception) use ($modeDev): void {
    http_response_code(500);

    if ($modeDev) {
        echo '<pre>' . htmlspecialchars((string) $exception, ENT_QUOTES) . '</pre>';

        return;
    }

    require __DIR__ . '/src/Vues/erreurs/500.php';
});

$routeur = new Routeur();

$routeur->get('/', static function (): void {
    header('Location: ' . Routeur::urlBase() . '/tableau-de-bord');
});

// EPIC 4 — Authentification & rôles
$routeur->get('/connexion', [AuthControleur::class, 'formulaire']);
$routeur->post('/connexion', [AuthControleur::class, 'connecter']);
$routeur->post('/deconnexion', [AuthControleur::class, 'deconnecter']);
$routeur->get('/tableau-de-bord', [TableauDeBordControleur::class, 'afficher']);

// Squelette du module Utilisateurs (EPIC 6) — sert ici de demo au controle d'acces par role.
$routeur->get('/utilisateurs', [UtilisateursControleur::class, 'liste']);

// Routes suivantes enrichies au fil des EPICs 5 a 8 (CRUD, commandes, API).

$routeur->distribuer($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
