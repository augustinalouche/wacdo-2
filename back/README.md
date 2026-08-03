# Wacdo — Back-end (Back-office + API)

Bloc 2 du projet Wacdo : back-office (gestion produits/menus/utilisateurs/commandes par rôle) et API consommée par le front.

## Statut

🚧 En cours de développement — fondations MVC + authentification/rôles opérationnelles (voir `EPIC 3`/`EPIC 4` et suivants du `BACKLOG.md` à la racine).

## Stack

- PHP (MVC maison, sans framework)
- MySQL (PDO, requêtes préparées)

## Installation

1. Créer la base de données (`wacdo2`) à partir du script SQL :
   ```
   mysql -u root < sql/schema.sql
   ```
   (ou via l'onglet "Importer" de phpMyAdmin)
2. Copier `config.example.php` en `config.php` et renseigner les accès BDD (`T03.8`, fichier hors dépôt public)
3. Servir le dossier via Apache (XAMPP) :

```
http://localhost/wacdo2/back/
```

## Comptes de test

Insérés via `sql/seed.sql`. Mot de passe identique pour les 3 comptes : `Wacdo2026!`

| Rôle | Email |
| --- | --- |
| Administration | admin@wacdo.test |
| Préparation | preparation@wacdo.test |
| Accueil | accueil@wacdo.test |

## Architecture

```
back/
├── index.php           # front controller (point d'entrée unique, déclare les routes)
├── .htaccess           # réécrit tout vers index.php (sauf fichiers/dossiers réels)
├── assets/
│   └── css/admin.css    # styles du back-office (mêmes tokens que le front)
├── config/
│   ├── config.php          # config locale (hors dépôt public)
│   └── config.example.php  # modèle à copier
├── sql/
│   ├── schema.sql       # MPD — création des tables
│   └── seed.sql         # données de test
└── src/
    ├── Core/            # Autoloader, Database (PDO), Routeur, Controleur et Modele abstraits
    ├── Securite/         # Auth (session, contrôle d'accès par rôle) et Csrf
    ├── Controleurs/       # AuthControleur, TableauDeBordControleur, UtilisateursControleur (squelette)
    ├── Modeles/            # Utilisateur (abstrait) + Administrateur/Preparateur/AgentAccueil, UtilisateurDepot
    └── Vues/
        ├── auth/connexion.php
        ├── tableau-de-bord.php
        ├── utilisateurs/liste.php   # squelette EPIC 6
        └── erreurs/                 # pages 403 / 404 / 500
```

## Authentification & rôles (`EPIC 4`)

- `POST /connexion` vérifie l'email + mot de passe (haché en base, `password_verify`) et le jeton CSRF, puis crée la session (`Securite\Auth::connecter`, avec régénération de l'identifiant de session).
- `POST /deconnexion` détruit la session.
- Chaque rôle (`Administrateur`, `Preparateur`, `AgentAccueil`) est une sous-classe de `Utilisateur` qui définit ses propres modules autorisés (`modulesAutorises()`) — c'est ce polymorphisme qui pilote le contrôle d'accès.
- `Securite\Auth::exigerConnexion()` protège une route (redirection vers `/connexion` si non authentifié) ; `Securite\Auth::exigerModule('xxx')` protège en plus par rôle (403 si le module n'est pas autorisé). Démonstration sur `GET /utilisateurs`, réservé à l'Administration.

## Endpoints API

_À documenter au fil du développement (`EPIC 8`, `T13.2`)._
