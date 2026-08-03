# Backlog — Projet Wacdo (Bloc 1 Front-end + Bloc 2 Back-end)

Légende : `[ ]` à faire, `[x]` terminé. Cochez au fur et à mesure.

---

## EPIC 0 — Cadrage & mise en place

- [x] **T00.1** — Créer la structure des dossiers du projet (ex. `/front` et `/back`, ou 2 dépôts séparés)
- [x] **T00.2** — Initialiser Git + créer le(s) dépôt(s) GitHub public(s)
- [x] **T00.3** — Rédiger le squelette des README (un par bloc, à compléter au fil du projet)
- [x] **T00.4** — Lister les assets fournis (images produits, logo, maquette) à intégrer, ou à défaut en créer des provisoires

---

## EPIC 1 — Conception (avant tout code)

- [x] **T01.1** — Lister les entités et leurs attributs : Utilisateur, Produit, Menu, CompositionMenu, Commande, LigneCommande, Statut, Catégorie *(voir `docs/conception/01-entites-et-relations.md`)*
- [x] **T01.2** — Définir les relations et cardinalités entre entités (1-N, N-N) et les contraintes (clés étrangères, unicité)
- [x] **T01.3** — Réaliser le schéma conceptuel de données (MCD) *(voir `docs/conception/02-mcd.md`)*
- [x] **T01.4** — Décliner le schéma physique de données (MPD) : tables, colonnes, types, clés primaires/étrangères *(voir `docs/conception/03-mpd.md` et `back/sql/schema.sql`)*
- [x] **T01.5** — Définir la structure JSON commune (produits, menus, commande envoyée) — **doit être identique** entre les fichiers JSON statiques et les futures réponses API *(voir `docs/conception/04-structure-json.md`)*
- [ ] **T01.6** — Réaliser le schéma fonctionnel (parcours client borne + parcours back-office par rôle, diagramme de cas d'utilisation)
- [ ] **T01.7** — Définir/adapter le wireframe des écrans de la borne (catalogue, panier, récapitulatif, saisie numéro)
- [ ] **T01.8** — Définir le wireframe des écrans back-office (login, dashboard par rôle, CRUD produits/menus, liste commandes)

---

## EPIC 2 — Bloc 1 : Front-end (borne) — mode JSON statique

- [x] **T02.1** — Squelette HTML sémantique des pages (accueil/catalogue, panier, récapitulatif) avec balises `header`/`nav`/`main`/`section`/`footer` *(voir `front/index.html`, appli mono-page à 5 écrans)*
- [x] **T02.2** — Intégration CSS de base à partir de la maquette (grille, typographie, couleurs) ciblant 1920×1080 *(voir `front/css/style.css`, tokens repris de `docs/maquette/modernist.css`)*
- [x] **T02.3** — Media queries / mise en page responsive pour résolutions inférieures (tablette, mobile si applicable) *(3 breakpoints dans `front/css/style.css`)*
- [x] **T02.4** — Créer/placer les fichiers `produits.json` et `menus.json` sur le serveur (structure définie en T01.5) *(voir `front/data/`)*
- [x] **T02.5** — Fonction AJAX (`fetch`) de récupération des produits + affichage dynamique du catalogue *(voir `front/js/api.js`, `front/js/render.js`)*
- [x] **T02.6** — Fonction AJAX de récupération des menus + affichage dynamique *(idem)*
- [x] **T02.7** — Filtrage/affichage par catégorie (si prévu dans la maquette) *(onglets `.filtre` dans `front/js/app.js`)*
- [x] **T02.8** — Logique panier : ajouter un produit simple, incrémenter/décrémenter quantité *(voir `front/js/panier.js`)*
- [x] **T02.9** — Logique panier : composer un menu (choix burger + frites/salade + boisson + sauce) *(modale de composition, `front/js/app.js`)*
- [x] **T02.10** — Gestion des tailles (accompagnement/boisson) avec supplément +0,50 € appliqué au prix
- [x] **T02.11** — Calcul et affichage du total en temps réel (unitaire + panier global)
- [x] **T02.12** — Vue panier détaillée : modifier quantité, supprimer une ligne
- [x] **T02.13** — Formulaire de saisie du numéro de commande + validation JS (champ requis, format)
- [x] **T02.14** — Écran récapitulatif final de la commande avant envoi
- [x] **T02.15** — Construction du JSON de commande final et envoi (vers mock/API) + gestion de l'accusé de réception *(simulation locale en attendant `EPIC 8`/`T10.1`)*
- [x] **T02.16** — Gestion des erreurs côté front (produit indisponible, échec réseau AJAX, panier vide)
- [x] **T02.17** — Passe accessibilité : `alt` sur images, labels de formulaire, contrastes, focus clavier, ARIA si besoin
- [x] **T02.18** — Passe SEO/sémantique : title, meta description, hiérarchie des titres
- [ ] **T02.19** — Vérification compatibilité navigateurs récents (Chrome/Firefox/Edge)
- [x] **T02.20** — Rédiger le README front (installation, structure des données JSON, scénarios de test) *(voir `front/README.md`)*

---

## EPIC 3 — Bloc 2 : Fondations Back-end (MVC maison)

- [x] **T03.1** — Créer la base de données à partir du MPD (script SQL `CREATE TABLE`) *(base `wacdo2`, voir `back/sql/schema.sql`)*
- [x] **T03.2** — Script de données de test (`INSERT`) : quelques produits, menus, utilisateurs de chaque rôle
- [x] **T03.3** — Mettre en place l'autoload des classes (PSR-4 simplifié ou autoload maison)
- [x] **T03.4** — Créer la classe de connexion à la base (PDO, singleton ou injection simple)
- [x] **T03.5** — Créer le routeur / front controller (point d'entrée unique, dispatch vers les contrôleurs)
- [x] **T03.6** — Définir la structure MVC (dossiers `Modeles`, `Controleurs`, `Vues`) + classe(s) abstraite(s) de base (ex. `Modele` abstrait, `Controleur` abstrait)
- [x] **T03.7** — Mettre en place la gestion des erreurs globales (404, 500) avec pages/réponses dédiées
- [x] **T03.8** — Mettre en place un fichier de configuration (accès BDD, environnement) hors du dépôt public (`.gitignore`)

---

## EPIC 4 — Bloc 2 : Authentification & rôles

- [x] **T04.1** — Modèle `Utilisateur` (classe de base) avec sous-classes ou attribut rôle (Administration / Préparation / Accueil) — mise en œuvre de l'héritage *(voir `back/src/Modeles/Utilisateur.php` + `Administrateur`/`Preparateur`/`AgentAccueil`, dépôt `UtilisateurDepot`)*
- [x] **T04.2** — Hashage des mots de passe (`password_hash`/`password_verify`) *(voir `UtilisateurDepot::creer`, `Utilisateur::verifierMotDePasse`)*
- [x] **T04.3** — Page/contrôleur de connexion (login) + création de session *(voir `AuthControleur`, `Vues/auth/connexion.php`)*
- [x] **T04.4** — Déconnexion (destruction de session) *(voir `AuthControleur::deconnecter`, `Auth::deconnecter`)*
- [x] **T04.5** — Middleware/vérification d'accès par rôle sur les routes du back-office (redirection ou 403 si non autorisé) *(voir `Securite/Auth::exigerConnexion`/`exigerModule`, testé sur `/utilisateurs`)*
- [x] **T04.6** — Protection CSRF sur les formulaires sensibles *(voir `Securite/Csrf`, formulaire de connexion)*

---

## EPIC 5 — Bloc 2 : Gestion Produits & Menus (rôle Administration)

- [ ] **T05.1** — Modèle + table Produit (CRUD complet : create/read/update/delete)
- [ ] **T05.2** — Vue liste des produits (back-office) + actions
- [ ] **T05.3** — Formulaire création/édition produit (nom, description, prix, image, catégorie, disponibilité)
- [ ] **T05.4** — Activation/désactivation d'un produit (disponibilité)
- [ ] **T05.5** — Upload/gestion de l'image produit
- [ ] **T05.6** — Modèle + table Menu + table de composition (liaison menu ↔ produits/options)
- [ ] **T05.7** — Formulaire création/édition d'un menu (sélection burger, accompagnement, boisson, sauce, options de taille)
- [ ] **T05.8** — Vue liste des menus + suppression
- [ ] **T05.9** — Validation serveur des formulaires (champs requis, prix numérique positif, etc.)

---

## EPIC 6 — Bloc 2 : Gestion des utilisateurs (rôle Administration)

- [ ] **T06.1** — CRUD des comptes utilisateurs internes (créer/modifier/supprimer)
- [ ] **T06.2** — Attribution/modification du rôle d'un utilisateur
- [ ] **T06.3** — Validation serveur (email/identifiant unique, mot de passe minimum)

---

## EPIC 7 — Bloc 2 : Gestion des commandes (rôles Accueil / Préparation)

- [ ] **T07.1** — Modèle Commande + LigneCommande (produits/menus, tailles, quantités, total)
- [ ] **T07.2** — Formulaire de saisie d'une commande par l'accueil (comptoir/téléphone)
- [ ] **T07.3** — Vue "commandes à préparer" triée par heure croissante (rôle Préparation)
- [ ] **T07.4** — Action "marquer préparée" (Préparation) avec contrôle de rôle
- [ ] **T07.5** — Vue des commandes prêtes à remettre (rôle Accueil)
- [ ] **T07.6** — Action "marquer livrée" (Accueil) avec contrôle de rôle
- [ ] **T07.7** — Historique/consultation des commandes passées (filtrage par statut/date)

---

## EPIC 8 — Bloc 2 : API

- [ ] **T08.1** — Endpoint `GET /api/produits` (avec filtre par catégorie optionnel) → JSON conforme à la structure T01.5
- [ ] **T08.2** — Endpoint `GET /api/menus` → JSON détaillé (composition incluse)
- [ ] **T08.3** — Endpoint `POST /api/commandes` : réception JSON, validation serveur complète, insertion en base
- [ ] **T08.4** — Réponses HTTP cohérentes (200/201/400/401/403/404/500) + corps JSON d'erreur structuré
- [ ] **T08.5** — Gestion des CORS si Front et API sont sur des origines différentes
- [ ] **T08.6** — Accusé de réception renvoyé au Front (numéro de commande, confirmation)

---

## EPIC 9 — Sécurité transverse

- [ ] **T09.1** — Vérifier que toutes les requêtes SQL utilisent des requêtes préparées (anti injection SQL)
- [ ] **T09.2** — Échapper les sorties HTML (anti XSS) dans les vues back-office
- [ ] **T09.3** — Vérifier le contrôle d'accès sur **chaque action** (pas seulement l'affichage des menus/liens)
- [ ] **T09.4** — Limiter les tentatives de connexion / messages d'erreur génériques (pas de fuite d'info)
- [ ] **T09.5** — Revue finale sécurité avant déploiement

---

## EPIC 10 — Intégration Front ↔ Back

- [ ] **T10.1** — Basculer les appels AJAX du Front des fichiers JSON statiques vers l'API réelle
- [ ] **T10.2** — Vérifier la cohérence exacte des structures de données (Front attend / API renvoie)
- [ ] **T10.3** — Test bout-en-bout : commande passée sur la borne → visible en back-office → traitée → statut mis à jour

---

## EPIC 11 — Tests & recette

- [ ] **T11.1** — Scénario : charger produits/menus, ajouter au panier, vérifier les calculs (tailles +0,50 €)
- [ ] **T11.2** — Scénario : validation commande → envoi JSON → accusé de réception reçu
- [ ] **T11.3** — Scénario : création/modification d'un produit en back-office → visible côté Front (si mode API)
- [ ] **T11.4** — Scénario : cycle de vie complet d'une commande (à préparer → préparée → livrée) avec restrictions de rôle
- [ ] **T11.5** — Scénario sécurité : un utilisateur non autorisé ne peut pas accéder à une page/action interdite (test direct par URL)
- [ ] **T11.6** — Test de compatibilité navigateurs + responsive (plusieurs résolutions)
- [ ] **T11.7** — Corriger les bugs détectés lors des tests

---

## EPIC 12 — Déploiement

- [ ] **T12.1** — Choisir/valider l'hébergement (Front + Back/API + BDD MySQL)
- [ ] **T12.2** — Déployer le Bloc 1 (Front)
- [ ] **T12.3** — Déployer le Bloc 2 (Back-office + API) + créer la BDD en production
- [ ] **T12.4** — Vérifier en production que le Front consomme bien l'API réelle
- [ ] **T12.5** — Créer des comptes de démonstration pour chaque rôle en production

---

## EPIC 13 — Documentation & livrables finaux

- [ ] **T13.1** — Finaliser le README Front (installation si besoin, lancement, structure des données, scénarios de test)
- [ ] **T13.2** — Finaliser le README Back (installation, configuration, comptes de test, endpoints API documentés)
- [ ] **T13.3** — Exporter le dump SQL final de la base de données
- [ ] **T13.4** — Mettre au propre les schémas (MCD, MPD, fonctionnel) en documents présentables (PDF/images)
- [ ] **T13.5** — Vérifier que les deux dépôts GitHub sont bien publics et à jour

---

## EPIC 14 — Préparation à la soutenance

- [ ] **T14.1** — Préparer une démonstration live fluide (scénario type du début à la fin)
- [ ] **T14.2** — Préparer l'argumentaire du modèle de données et des choix d'architecture MVC
- [ ] **T14.3** — S'entraîner à une modification de code en direct (ex. ajouter un champ, changer une règle métier simple)
- [ ] **T14.4** — Relire le référentiel de certification (RNCP 37805) pour anticiper les questions du jury

---

*Total : ~14 epics, ~85 tickets. Cochez-les au fur et à mesure de l'avancement.*
