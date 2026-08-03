# Conception — Structure JSON commune (T01.5)

Cette structure est **strictement identique** entre :
- les fichiers JSON statiques consommés par le front en mode maquette (`front/data/produits.json`, `front/data/menus.json`)
- les réponses de la future API (`EPIC 8` : `GET /api/produits`, `GET /api/menus`)
- le JSON envoyé par le front à l'API lors de la validation d'une commande (`POST /api/commandes`)

Les identifiants (`id`) correspondent exactement aux clés primaires de la base `wacdo2` — pas de slugs texte — pour qu'une commande puisse être renvoyée telle quelle vers l'API sans table de correspondance.

---

## 1. `produits.json`

Tableau à plat. `prix` est **soit un nombre** (burger, dessert — pas de choix de taille), **soit un objet** `{ petite, grande }` (accompagnement, boisson).

```json
[
  {
    "id": 1,
    "nom": "Le Classique",
    "description": "Steak haché, cheddar, oignons, cornichons, sauce burger",
    "categorie": "burger",
    "image": "Burger-classique.png",
    "disponible": true,
    "prix": 4.90
  },
  {
    "id": 7,
    "nom": "Frites",
    "description": "Pommes de terre fraîches, cuisson croustillante",
    "categorie": "accompagnement",
    "image": "Frites.png",
    "disponible": true,
    "prix": { "petite": 2.20, "grande": 2.70 }
  }
]
```

`categorie` ∈ `burger | accompagnement | boisson | dessert`. Le supplément "Grande taille" (+0,50€) est **déjà inclus** dans le prix `grande` — le front n'a pas à l'ajouter une seconde fois.

## 2. `menus.json`

```json
{
  "sauces": [
    { "id": 1, "nom": "Ketchup" },
    { "id": 2, "nom": "Mayonnaise" }
  ],
  "menus": [
    {
      "id": 1,
      "nom": "Menu Classique",
      "description": "Le Classique, un accompagnement, une boisson et une sauce au choix",
      "prixBase": 7.90,
      "burgerId": 1,
      "image": "Burger-classique-menu.png",
      "disponible": true
    }
  ]
}
```

`burgerId` référence un `id` de `produits.json` (catégorie `burger`). Les choix d'accompagnement/boisson/sauce d'un menu ne sont **pas** listés ici : ils sont toujours "tous les produits de catégorie `accompagnement`", "tous ceux de catégorie `boisson`", et "toutes les `sauces`" (règle métier commune à tous les menus).

## 3. Commande envoyée par le front (`POST /api/commandes`)

Une ligne est **soit** un produit simple (`type: "produit"`), **soit** un menu (`type: "menu"`) — jamais les deux (cf. `RG5`).

```json
{
  "numero": "125",
  "origine": "borne",
  "lignes": [
    {
      "type": "produit",
      "produitId": 5,
      "tailleId": null,
      "quantite": 2
    },
    {
      "type": "produit",
      "produitId": 9,
      "tailleId": 2,
      "quantite": 1
    },
    {
      "type": "menu",
      "menuId": 1,
      "quantite": 1,
      "composition": {
        "accompagnementProduitId": 7,
        "accompagnementTailleId": 2,
        "boissonProduitId": 9,
        "boissonTailleId": 1,
        "sauceId": 1
      }
    }
  ]
}
```

- `tailleId` est `null` pour un produit sans taille (burger, dessert), ou l'`id` d'une entrée de `taille` pour un accompagnement/boisson acheté seul.
- Le prix n'est **jamais envoyé par le front** : l'API recalcule et fige `prix_unitaire` côté serveur à partir du catalogue (sécurité — ne jamais faire confiance à un prix client, `T09.x`).
- `numero` est le numéro de récupération saisi par le client (`T02.13`) — il devient `commande.numero_affichage` en base.

## 4. Accusé de réception de l'API (`T08.6`)

```json
{
  "succes": true,
  "commandeId": 42,
  "numero": "125",
  "message": "Commande enregistrée."
}
```

En cas d'erreur (`T08.4`) :

```json
{
  "succes": false,
  "erreur": "Produit indisponible : Cookie Pépites"
}
```

## 5. Fichiers de référence

Les fichiers statiques réels, alignés sur les données de test de `back/sql/seed.sql`, sont dans `front/data/produits.json` et `front/data/menus.json` (`T02.4`).
