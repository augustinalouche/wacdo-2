# Wacdo — Front-end (Borne de commande)

Bloc 1 du projet Wacdo : interface borne tactile pour passer une commande (catalogue, panier, récapitulatif).

## Statut

✅ Application mono-page fonctionnelle, branchée sur l'API réelle du back-office (`EPIC 2`, `EPIC 10` du `BACKLOG.md` à la racine). Reste : tests multi-navigateurs (`T02.19`).

## Stack

- HTML5 sémantique
- CSS3 (media queries, responsive, cible principale 1920×1080)
- JavaScript vanilla (`fetch`/AJAX, pas de framework, pas de build)

## Installation

Aucune dépendance à installer. Servir le dossier via un serveur local (ex. XAMPP/Apache) :

```
http://localhost/wacdo2/front/
```

## Architecture

```
front/
├── index.html          # appli mono-page : 5 écrans (<section>) affichés/masqués en JS
├── css/
│   └── style.css       # tokens repris de docs/maquette/modernist.css + responsive
├── js/
│   ├── api.js           # accès à l'API back-office (GET /api/produits, /api/menus, POST /api/commandes)
│   ├── panier.js        # état et calculs du panier (ajout, quantités, totaux, JSON de commande)
│   ├── render.js         # construction du DOM (catalogue, panier, récapitulatif)
│   └── app.js            # orchestration : navigation, formulaires, modales, événements
├── data/
│   ├── produits.json     # ancienne maquette de données (EPIC 2) — gardée en référence, plus utilisée par l'appli
│   └── menus.json         # idem, structure documentée dans docs/conception/04-structure-json.md
└── img/                  # visuels produits/menus
```

## Parcours utilisateur

1. **Catalogue** — filtres par catégorie (Tout, Menus, Burgers, Accompagnements, Boissons, Desserts), ajout direct (burger/dessert), choix de taille (accompagnement/boisson), composition guidée (menu).
2. **Panier** — quantités, suppression de ligne, total en temps réel.
3. **Numéro de commande** — saisie et validation (1 à 999).
4. **Récapitulatif** — relecture avant envoi.
5. **Confirmation** — numéro de commande à présenter au comptoir.

## Branchement sur l'API (`T10.1`)

Le front appelle directement l'API du Bloc 2 (`front/js/api.js`, `URL_API_BASE = '/wacdo2/back/api'`) :

- `GET /api/produits`, `GET /api/menus` pour charger le catalogue.
- `POST /api/commandes` pour valider une commande — le message d'erreur affiché à l'écran (produit indisponible, réseau…) est directement celui renvoyé par l'API.

Aucun autre fichier front (`panier.js`, `render.js`, `app.js`) n'a eu besoin de changer pour ce basculement : la structure JSON de l'API est strictement identique à celle des anciens fichiers statiques (`docs/conception/04-structure-json.md`, `T01.5`).

Le back-office doit être démarré (Apache + MySQL, base `wacdo2`) pour que le front fonctionne — voir `back/README.md`.

## Scénarios de test

- Charger la page avec le back-office arrêté (Apache/MySQL coupés) → un message d'erreur visible doit s'afficher à la place du catalogue.
- Ajouter un burger, un accompagnement (choix de taille), une boisson (choix de taille), un dessert, puis composer un menu complet → vérifier que le total du panier correspond à la somme attendue (suppléments grande taille inclus).
- Vider le panier (supprimer toutes les lignes) → le bouton « Valider ma commande » doit être désactivé.
- Saisir un numéro invalide (vide, 0, 1000, texte) → message d'erreur affiché, pas de passage au récapitulatif.
- Confirmer une commande → écran de confirmation avec le numéro saisi, panier vidé, badge remis à 0.
- Réduire la fenêtre à une largeur mobile (~375px) → la grille produits passe en une colonne et les boutons restent utilisables.
- Naviguer entièrement au clavier (Tab/Entrée/Échap) → tous les boutons, filtres, champs et modales doivent être atteignables et l'ordre de tabulation cohérent.
