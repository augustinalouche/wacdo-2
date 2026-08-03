<?php

declare(strict_types=1);

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

// Routes de base — enrichies au fil des EPICs 4 a 8 (auth, CRUD, commandes, API).
$routeur->get('/', static function (): void {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Wacdo back-office — en construction.\n";

    try {
        \Wacdo\Core\Database::connexion();
        echo "Connexion base de donnees : OK\n";
    } catch (\Throwable $exception) {
        echo "Connexion base de donnees : ECHEC — " . $exception->getMessage() . "\n";
    }
});

$routeur->distribuer($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
