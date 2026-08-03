# Wacdo — Back-end (Back-office + API)

Bloc 2 du projet Wacdo : back-office (gestion produits/menus/utilisateurs/commandes par rôle) et API consommée par le front.

## Statut

🚧 En cours de développement — fondations MVC (voir `EPIC 3` et suivants du `BACKLOG.md` à la racine).

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
├── index.php           # front controller (point d'entrée unique)
├── .htaccess           # réécrit tout vers index.php
├── config/
│   ├── config.php          # config locale (hors dépôt public)
│   └── config.example.php  # modèle à copier
├── sql/
│   ├── schema.sql       # MPD — création des tables
│   └── seed.sql         # données de test
└── src/
    ├── Core/            # Autoloader, Database (PDO), Routeur, Controleur et Modele abstraits
    ├── Controleurs/      # (à venir — EPIC 4 et suivants)
    ├── Modeles/          # (à venir)
    └── Vues/
        └── erreurs/      # pages 404 / 500
```

## Endpoints API

_À documenter au fil du développement (`EPIC 8`, `T13.2`)._
