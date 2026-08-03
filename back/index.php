<?php

declare(strict_types=1);

use Wacdo\Controleurs\AuthControleur;
use Wacdo\Controleurs\MenusControleur;
use Wacdo\Controleurs\ProduitsControleur;
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

// EPIC 6 — Gestion des utilisateurs (role Administration)
$routeur->get('/utilisateurs', [UtilisateursControleur::class, 'liste']);
$routeur->get('/utilisateurs/nouveau', [UtilisateursControleur::class, 'nouveauFormulaire']);
$routeur->post('/utilisateurs', [UtilisateursControleur::class, 'creer']);
$routeur->get('/utilisateurs/{id}/editer', [UtilisateursControleur::class, 'editerFormulaire']);
$routeur->post('/utilisateurs/{id}', [UtilisateursControleur::class, 'modifier']);
$routeur->post('/utilisateurs/{id}/statut', [UtilisateursControleur::class, 'basculerActif']);
$routeur->post('/utilisateurs/{id}/supprimer', [UtilisateursControleur::class, 'supprimer']);

// EPIC 5 — Gestion Produits & Menus (role Administration)
$routeur->get('/produits', [ProduitsControleur::class, 'liste']);
$routeur->get('/produits/nouveau', [ProduitsControleur::class, 'nouveauFormulaire']);
$routeur->post('/produits', [ProduitsControleur::class, 'creer']);
$routeur->get('/produits/{id}/editer', [ProduitsControleur::class, 'editerFormulaire']);
$routeur->post('/produits/{id}', [ProduitsControleur::class, 'modifier']);
$routeur->post('/produits/{id}/disponibilite', [ProduitsControleur::class, 'basculerDisponibilite']);
$routeur->post('/produits/{id}/supprimer', [ProduitsControleur::class, 'supprimer']);

$routeur->get('/menus', [MenusControleur::class, 'liste']);
$routeur->get('/menus/nouveau', [MenusControleur::class, 'nouveauFormulaire']);
$routeur->post('/menus', [MenusControleur::class, 'creer']);
$routeur->get('/menus/{id}/editer', [MenusControleur::class, 'editerFormulaire']);
$routeur->post('/menus/{id}', [MenusControleur::class, 'modifier']);
$routeur->post('/menus/{id}/supprimer', [MenusControleur::class, 'supprimer']);

// Routes suivantes enrichies au fil des EPICs 7 a 8 (commandes, API).

$routeur->distribuer($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
