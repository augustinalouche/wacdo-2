# Conception — Schéma physique de données / MPD (T01.4)

Déclinaison physique du MCD (`02-mcd.md`) : tables, colonnes, types MySQL, clés primaires/étrangères. Le script SQL exécutable correspondant est dans `back/sql/schema.sql`.

---

## Tables

### `categorie`

| Colonne | Type | Contrainte |
| --- | --- | --- |
| id | `INT AUTO_INCREMENT` | PK |
| nom | `VARCHAR(50)` | NOT NULL, UNIQUE |

### `produit`

| Colonne | Type | Contrainte |
| --- | --- | --- |
| id | `INT AUTO_INCREMENT` | PK |
| nom | `VARCHAR(100)` | NOT NULL |
| description | `TEXT` | NULL |
| prix | `DECIMAL(6,2)` | NULL (rempli si le produit n'a pas de taille) |
| categorie_id | `INT` | NOT NULL, FK → `categorie(id)` |
| image | `VARCHAR(255)` | NULL |
| disponible | `TINYINT(1)` | NOT NULL, DEFAULT 1 |

### `taille`

| Colonne | Type | Contrainte |
| --- | --- | --- |
| id | `INT AUTO_INCREMENT` | PK |
| libelle | `VARCHAR(20)` | NOT NULL, UNIQUE |
| supplement | `DECIMAL(4,2)` | NOT NULL, DEFAULT 0 |

### `produit_taille`

| Colonne | Type | Contrainte |
| --- | --- | --- |
| produit_id | `INT` | PK (composite), FK → `produit(id)` |
| taille_id | `INT` | PK (composite), FK → `taille(id)` |
| prix | `DECIMAL(6,2)` | NOT NULL |

### `sauce`

| Colonne | Type | Contrainte |
| --- | --- | --- |
| id | `INT AUTO_INCREMENT` | PK |
| nom | `VARCHAR(50)` | NOT NULL, UNIQUE |

### `menu`

| Colonne | Type | Contrainte |
| --- | --- | --- |
| id | `INT AUTO_INCREMENT` | PK |
| nom | `VARCHAR(100)` | NOT NULL |
| description | `TEXT` | NULL |
| prix_base | `DECIMAL(6,2)` | NOT NULL |
| burger_id | `INT` | NOT NULL, UNIQUE, FK → `produit(id)` |
| image | `VARCHAR(255)` | NULL |
| disponible | `TINYINT(1)` | NOT NULL, DEFAULT 1 |

### `statut`

| Colonne | Type | Contrainte |
| --- | --- | --- |
| id | `INT AUTO_INCREMENT` | PK |
| libelle | `VARCHAR(30)` | NOT NULL, UNIQUE |

### `utilisateur`

| Colonne | Type | Contrainte |
| --- | --- | --- |
| id | `INT AUTO_INCREMENT` | PK |
| nom | `VARCHAR(50)` | NOT NULL |
| prenom | `VARCHAR(50)` | NOT NULL |
| email | `VARCHAR(150)` | NOT NULL, UNIQUE |
| mot_de_passe | `VARCHAR(255)` | NOT NULL (hash `password_hash`) |
| role | `ENUM('administration','preparation','accueil')` | NOT NULL |
| actif | `TINYINT(1)` | NOT NULL, DEFAULT 1 |
| date_creation | `DATETIME` | NOT NULL, DEFAULT `CURRENT_TIMESTAMP` |

### `commande`

| Colonne | Type | Contrainte |
| --- | --- | --- |
| id | `INT AUTO_INCREMENT` | PK |
| numero_affichage | `VARCHAR(20)` | NOT NULL |
| date_heure | `DATETIME` | NOT NULL, DEFAULT `CURRENT_TIMESTAMP` |
| statut_id | `INT` | NOT NULL, FK → `statut(id)` |
| montant_total | `DECIMAL(8,2)` | NOT NULL |
| origine | `ENUM('borne','comptoir','telephone')` | NOT NULL |
| utilisateur_id | `INT` | NULL, FK → `utilisateur(id)` |

### `ligne_commande`

| Colonne | Type | Contrainte |
| --- | --- | --- |
| id | `INT AUTO_INCREMENT` | PK |
| commande_id | `INT` | NOT NULL, FK → `commande(id)` ON DELETE CASCADE |
| produit_id | `INT` | NULL, FK → `produit(id)` |
| menu_id | `INT` | NULL, FK → `menu(id)` |
| taille_id | `INT` | NULL, FK → `taille(id)` |
| quantite | `INT` | NOT NULL, CHECK > 0 |
| prix_unitaire | `DECIMAL(6,2)` | NOT NULL |

Contrainte d'exclusivité (`RG5`) : `CHECK ((produit_id IS NULL) <> (menu_id IS NULL))` — une ligne référence un produit **ou** un menu, jamais les deux, jamais aucun.

### `composition_menu`

| Colonne | Type | Contrainte |
| --- | --- | --- |
| id | `INT AUTO_INCREMENT` | PK |
| ligne_commande_id | `INT` | NOT NULL, UNIQUE, FK → `ligne_commande(id)` ON DELETE CASCADE |
| accompagnement_produit_id | `INT` | NOT NULL, FK → `produit(id)` |
| accompagnement_taille_id | `INT` | NOT NULL, FK → `taille(id)` |
| boisson_produit_id | `INT` | NOT NULL, FK → `produit(id)` |
| boisson_taille_id | `INT` | NOT NULL, FK → `taille(id)` |
| sauce_id | `INT` | NOT NULL, FK → `sauce(id)` |

---

## Choix de conception

- **Suppression logique, pas physique** des produits/menus : la colonne `disponible` sert de "soft delete" (`T05.4`). Les FK vers `produit`/`menu`/`categorie`/`taille`/`statut` restent donc en `ON DELETE RESTRICT` (comportement par défaut) pour ne jamais casser l'historique des commandes.
- **`utilisateur_id` sur `commande`** est en `ON DELETE SET NULL` : si un compte employé est supprimé, l'historique de commande est conservé mais perd le lien vers l'agent.
- **Cascade** uniquement sur les enfants directs d'une commande (`ligne_commande`, puis `composition_menu`) : supprimer une commande supprime proprement ses lignes et leur composition.
- **Prix figés** : `produit_taille.prix` et `ligne_commande.prix_unitaire` sont stockés en dur (pas de recalcul dynamique) pour ne jamais altérer une facture passée si le catalogue change.

## Prochaine étape

`T03.1` — Exécuter `back/sql/schema.sql` pour créer la base de données, puis `T03.2` pour y insérer des données de test.
