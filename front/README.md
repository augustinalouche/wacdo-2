# Wacdo — Front-end (Borne de commande)

Bloc 1 du projet Wacdo : interface borne tactile pour passer une commande (catalogue, panier, récapitulatif).

## Statut

✅ Application mono-page fonctionnelle en mode JSON statique (`EPIC 2` du `BACKLOG.md` à la racine). Reste : tests multi-navigateurs (`T02.19`) et branchement sur l'API réelle une fois `EPIC 8`/`T10.1` livrés.

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
│   ├── api.js           # accès data/*.json + envoi de la commande (simulation si API absente)
│   ├── panier.js        # état et calculs du panier (ajout, quantités, totaux, JSON de commande)
│   ├── render.js         # construction du DOM (catalogue, panier, récapitulatif)
│   └── app.js            # orchestration : navigation, formulaires, modales, événements
├── data/
│   ├── produits.json     # catalogue produits (structure : voir docs/conception/04-structure-json.md)
│   └── menus.json         # menus + sauces disponibles
└── img/                  # visuels produits/menus
```

## Parcours utilisateur

1. **Catalogue** — filtres par catégorie (Tout, Menus, Burgers, Accompagnements, Boissons, Desserts), ajout direct (burger/dessert), choix de taille (accompagnement/boisson), composition guidée (menu).
2. **Panier** — quantités, suppression de ligne, total en temps réel.
3. **Numéro de commande** — saisie et validation (1 à 999).
4. **Récapitulatif** — relecture avant envoi.
5. **Confirmation** — numéro de commande à présenter au comptoir.

## Structure des données

Les fichiers JSON statiques (`data/produits.json`, `data/menus.json`) sont utilisés en attendant l'API réelle du Bloc 2. Leur structure — y compris le JSON envoyé lors de la validation d'une commande — est décrite dans `docs/conception/04-structure-json.md` (`T01.5`) et doit rester identique à celle de l'API.

## Scénarios de test

- Charger la page sans connexion aux JSON (renommer temporairement `data/produits.json`) → un message d'erreur visible doit s'afficher à la place du catalogue.
- Ajouter un burger, un accompagnement (choix de taille), une boisson (choix de taille), un dessert, puis composer un menu complet → vérifier que le total du panier correspond à la somme attendue (suppléments grande taille inclus).
- Vider le panier (supprimer toutes les lignes) → le bouton « Valider ma commande » doit être désactivé.
- Saisir un numéro invalide (vide, 0, 1000, texte) → message d'erreur affiché, pas de passage au récapitulatif.
- Confirmer une commande → écran de confirmation avec le numéro saisi, panier vidé, badge remis à 0.
- Réduire la fenêtre à une largeur mobile (~375px) → la grille produits passe en une colonne et les boutons restent utilisables.
- Naviguer entièrement au clavier (Tab/Entrée/Échap) → tous les boutons, filtres, champs et modales doivent être atteignables et l'ordre de tabulation cohérent.
