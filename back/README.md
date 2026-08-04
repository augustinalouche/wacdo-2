# Wacdo — Back-end (Back-office + API)

Bloc 2 du projet Wacdo : back-office (gestion produits/menus/utilisateurs/commandes par rôle) et API consommée par le front.

## Statut

✅ Back-office complet (authentification/rôles, produits/menus/utilisateurs/commandes) + API publique, consommée par le front en conditions réelles (voir `EPIC 3` à `EPIC 8` et `EPIC 10` du `BACKLOG.md` à la racine).

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
    ├── Core/            # Autoloader, Database (PDO), Routeur, Controleur/Modele abstraits, Validation, Flash, TeleversementImage
    ├── Securite/         # Auth (session, contrôle d'accès par rôle) et Csrf
    ├── Controleurs/
    │   ├── Api/            # ProduitsApiControleur, MenusApiControleur, CommandesApiControleur (EPIC 8)
    │   ├── AuthControleur.php, TableauDeBordControleur.php
    │   └── ProduitsControleur.php, MenusControleur.php, UtilisateursControleur.php, CommandesControleur.php
    ├── Modeles/            # Utilisateur (+ sous-classes par rôle), Produit, Menu, Commande/LigneCommande, dépôts (*Depot) et ReferentielDepot
    └── Vues/
        ├── auth/connexion.php
        ├── tableau-de-bord.php
        ├── produits/ , menus/ , utilisateurs/   # liste + formulaire (création/édition) pour chaque module
        ├── commandes/                            # liste (file de préparation/remise/historique) + formulaire de saisie
        └── erreurs/                              # pages 403 / 404 / 500
```

## Authentification & rôles (`EPIC 4`)

- `POST /connexion` vérifie l'email + mot de passe (haché en base, `password_verify`) et le jeton CSRF, puis crée la session (`Securite\Auth::connecter`, avec régénération de l'identifiant de session).
- `POST /deconnexion` détruit la session.
- Chaque rôle (`Administrateur`, `Preparateur`, `AgentAccueil`) est une sous-classe de `Utilisateur` qui définit ses propres modules autorisés (`modulesAutorises()`) — c'est ce polymorphisme qui pilote le contrôle d'accès.
- `Securite\Auth::exigerConnexion()` protège une route (redirection vers `/connexion` si non authentifié) ; `Securite\Auth::exigerModule('xxx')` protège en plus par rôle (403 si le module n'est pas autorisé). Démonstration sur `GET /utilisateurs`, réservé à l'Administration.

## Gestion Produits & Menus (`EPIC 5`)

- `/produits` et `/menus` (CRUD complet) sont réservés au module `produits`/`menus` (rôle Administration).
- Les catégories `accompagnement` et `boisson` ont un prix par taille (`produit_taille`) au lieu d'un prix fixe — géré dynamiquement dans le formulaire (`Produit::categorieUtiliseDesTailles`).
- Un menu est toujours associé à un burger **non déjà utilisé par un autre menu** (contrainte `uq_menu_burger`) ; l'accompagnement/boisson/sauce restent au choix du client à la commande, pas figés par menu.
- L'image envoyée via le formulaire est validée (extension, taille ≤ 2 Mo) puis enregistrée directement dans `front/img/` (`Core\TeleversementImage`) : le front peut l'utiliser immédiatement, sans étape de synchronisation.
- Toutes les actions de modification (créer/modifier/activer/supprimer) vérifient le jeton CSRF (`Securite\Csrf`).

## Gestion des utilisateurs (`EPIC 6`)

- `/utilisateurs` (CRUD complet) est réservé au module `utilisateurs` (rôle Administration).
- Email unique vérifié en base (`UtilisateurDepot::emailExiste`) en plus de la contrainte SQL, mot de passe d'au moins 8 caractères (vide = inchangé en édition).
- Un compte ne peut ni se supprimer, ni se désactiver, ni changer son propre rôle (garde-fous côté contrôleur ET champs désactivés côté formulaire).

## Gestion des commandes (`EPIC 7`)

- `/commandes` est réservé au module `commandes` (Administration, Préparation, Accueil), mais chaque action fine est restreinte au rôle métier concerné :
  - Saisie d'une commande comptoir/téléphone (`/commandes/nouvelle`) : Accueil + Administration.
  - « Marquer préparée » : Préparation + Administration.
  - « Marquer livrée » : Accueil + Administration.
- La liste `/commandes` adapte sa vue par défaut au rôle connecté : file « à préparer » (statut `En attente`, triée par heure croissante) pour Préparation, « prêtes à remettre » (statut `Prête`) pour Accueil, historique complet pour Administration. Un formulaire de filtres (statut, plage de dates) est disponible pour les trois rôles (`T07.7`).
- `CommandeDepot::creer()` recalcule et fige le prix de chaque ligne à partir du catalogue courant (prix produit, prix par taille, ou prix de base du menu + suppléments de taille de la composition) : **le prix n'est jamais accepté depuis le formulaire**, même règle que la future API (`docs/conception/04-structure-json.md`, `T09.x`).
- La création d'une commande (en-tête + lignes + compositions de menu) est transactionnelle : soit tout est inséré, soit rien ne l'est (`PDO::beginTransaction`/`commit`/`rollBack`).

## Endpoints API (`EPIC 8`)

Toutes les réponses sont en `application/json; charset=utf-8`. Les structures ci-dessous sont **strictement identiques** à `front/data/produits.json`/`menus.json` (voir `docs/conception/04-structure-json.md`) : le front peut basculer des fichiers statiques vers l'API réelle sans rien changer côté appelant (`T10.1`).

| Méthode | Route | Description |
| --- | --- | --- |
| `GET` | `/api/produits` | Liste des produits. Filtre optionnel `?categorie=burger\|accompagnement\|boisson\|dessert`. |
| `GET` | `/api/menus` | `{ sauces: [...], menus: [...] }`. |
| `POST` | `/api/commandes` | Reçoit `{ numero, origine, lignes: [...] }`, valide, insère en base, renvoie l'accusé de réception. |

- **Sécurité prix** : `POST /api/commandes` ne fait jamais confiance au prix (il n'y en a d'ailleurs aucun dans le JSON envoyé) — `CommandeDepot::creer()` (réutilisé de l'`EPIC 7`) recalcule chaque `prix_unitaire` à partir du catalogue courant.
- **Réponses d'erreur** structurées et cohérentes (`T08.4`) : `{ "succes": false, "erreur": "..." }` avec le code HTTP approprié (`400` validation, `404` route API inconnue, `500` erreur serveur via le gestionnaire d'exceptions global).
- **Accusé de réception** (`T08.6`, code `201`) : `{ "succes": true, "commandeId": 42, "numero": "125", "message": "Commande enregistrée." }`.
- **CORS** (`T08.5`) : `Routeur` ajoute les en-têtes `Access-Control-Allow-*` sur toute route `/api/*` et répond `204` aux requêtes préliminaires `OPTIONS` ; l'origine autorisée se configure via `config['cors']['origine_autorisee']` (`*` en développement, à restreindre en production).
