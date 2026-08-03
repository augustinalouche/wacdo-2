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

_À compléter (`T03.2`, `T13.2`)._

## Endpoints API

_À documenter au fil du développement (`EPIC 8`, `T13.2`)._
