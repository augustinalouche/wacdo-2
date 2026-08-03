-- Wacdo — Donnees de test (T03.2)
-- A executer APRES schema.sql : mysql -u root wacdo2 < sql/seed.sql

USE wacdo2;

-- ---------------------------------------------------------------------------
-- Categories
-- ---------------------------------------------------------------------------
INSERT INTO categorie (nom) VALUES
  ('burger'), ('accompagnement'), ('boisson'), ('dessert');

-- ---------------------------------------------------------------------------
-- Tailles
-- ---------------------------------------------------------------------------
INSERT INTO taille (libelle, supplement) VALUES
  ('Petite', 0.00),
  ('Grande', 0.50);

-- ---------------------------------------------------------------------------
-- Statuts
-- ---------------------------------------------------------------------------
INSERT INTO statut (libelle) VALUES
  ('En attente'), ('En preparation'), ('Prete'), ('Livree');

-- ---------------------------------------------------------------------------
-- Sauces
-- ---------------------------------------------------------------------------
INSERT INTO sauce (nom) VALUES
  ('Ketchup'), ('Mayonnaise'), ('Barbecue'), ('Algérienne'), ('Moutarde');

-- ---------------------------------------------------------------------------
-- Produits — burgers (prix fixe, sans taille)
-- ---------------------------------------------------------------------------
INSERT INTO produit (nom, description, prix, categorie_id, image, disponible) VALUES
  ('Le Classique', 'Steak haché, cheddar, oignons, cornichons, sauce burger', 4.90,
    (SELECT id FROM categorie WHERE nom = 'burger'), 'burger-classique.png', 1),
  ('Le Bacon Crousti', 'Steak haché, bacon fumé, cheddar, sauce barbecue', 5.90,
    (SELECT id FROM categorie WHERE nom = 'burger'), 'burger-bacon.png', 1),
  ('Le Poulet Croustillant', 'Filet de poulet pané, salade, tomate, sauce fromagère', 5.50,
    (SELECT id FROM categorie WHERE nom = 'burger'), 'burger-poulet.png', 1),
  ('Le Veggie', 'Galette de légumes, cheddar, salade, sauce curry doux', 5.20,
    (SELECT id FROM categorie WHERE nom = 'burger'), 'burger-veggie.png', 1);

-- ---------------------------------------------------------------------------
-- Produits — desserts (prix fixe, sans taille)
-- ---------------------------------------------------------------------------
INSERT INTO produit (nom, description, prix, categorie_id, image, disponible) VALUES
  ('Cookie Pépites', 'Cookie moelleux aux pépites de chocolat', 1.90,
    (SELECT id FROM categorie WHERE nom = 'dessert'), 'cookie.png', 1),
  ('Fondant au Chocolat', 'Cœur coulant au chocolat noir', 2.90,
    (SELECT id FROM categorie WHERE nom = 'dessert'), 'fondant.png', 1);

-- ---------------------------------------------------------------------------
-- Produits — accompagnements et boissons (prix NULL, defini par taille)
-- ---------------------------------------------------------------------------
INSERT INTO produit (nom, description, prix, categorie_id, image, disponible) VALUES
  ('Frites', 'Pommes de terre fraîches, cuisson croustillante', NULL,
    (SELECT id FROM categorie WHERE nom = 'accompagnement'), 'frites.png', 1),
  ('Salade Verte', 'Mélange de jeunes pousses, vinaigrette légère', NULL,
    (SELECT id FROM categorie WHERE nom = 'accompagnement'), 'salade.png', 1),
  ('Cola', 'Boisson gazeuse rafraîchissante', NULL,
    (SELECT id FROM categorie WHERE nom = 'boisson'), 'cola.png', 1),
  ('Eau Minérale', 'Eau plate en bouteille', NULL,
    (SELECT id FROM categorie WHERE nom = 'boisson'), 'eau.png', 1),
  ('Jus d''Orange', '100% pur jus, sans sucres ajoutés', NULL,
    (SELECT id FROM categorie WHERE nom = 'boisson'), 'jus-orange.png', 1);

-- ---------------------------------------------------------------------------
-- Prix par taille (accompagnements, boissons)
-- ---------------------------------------------------------------------------
INSERT INTO produit_taille (produit_id, taille_id, prix) VALUES
  ((SELECT id FROM produit WHERE nom = 'Frites'), (SELECT id FROM taille WHERE libelle = 'Petite'), 2.20),
  ((SELECT id FROM produit WHERE nom = 'Frites'), (SELECT id FROM taille WHERE libelle = 'Grande'), 2.70),
  ((SELECT id FROM produit WHERE nom = 'Salade Verte'), (SELECT id FROM taille WHERE libelle = 'Petite'), 2.40),
  ((SELECT id FROM produit WHERE nom = 'Salade Verte'), (SELECT id FROM taille WHERE libelle = 'Grande'), 2.90),
  ((SELECT id FROM produit WHERE nom = 'Cola'), (SELECT id FROM taille WHERE libelle = 'Petite'), 1.90),
  ((SELECT id FROM produit WHERE nom = 'Cola'), (SELECT id FROM taille WHERE libelle = 'Grande'), 2.40),
  ((SELECT id FROM produit WHERE nom = 'Eau Minérale'), (SELECT id FROM taille WHERE libelle = 'Petite'), 1.60),
  ((SELECT id FROM produit WHERE nom = 'Eau Minérale'), (SELECT id FROM taille WHERE libelle = 'Grande'), 2.10),
  ((SELECT id FROM produit WHERE nom = 'Jus d''Orange'), (SELECT id FROM taille WHERE libelle = 'Petite'), 2.00),
  ((SELECT id FROM produit WHERE nom = 'Jus d''Orange'), (SELECT id FROM taille WHERE libelle = 'Grande'), 2.50);

-- ---------------------------------------------------------------------------
-- Menus (1 par burger)
-- ---------------------------------------------------------------------------
INSERT INTO menu (nom, description, prix_base, burger_id, image, disponible) VALUES
  ('Menu Classique', 'Le Classique, un accompagnement, une boisson et une sauce au choix', 7.90,
    (SELECT id FROM produit WHERE nom = 'Le Classique'), 'menu-classique.png', 1),
  ('Menu Bacon Crousti', 'Le Bacon Crousti, un accompagnement, une boisson et une sauce au choix', 8.90,
    (SELECT id FROM produit WHERE nom = 'Le Bacon Crousti'), 'menu-bacon.png', 1),
  ('Menu Poulet Croustillant', 'Le Poulet Croustillant, un accompagnement, une boisson et une sauce au choix', 8.50,
    (SELECT id FROM produit WHERE nom = 'Le Poulet Croustillant'), 'menu-poulet.png', 1),
  ('Menu Veggie', 'Le Veggie, un accompagnement, une boisson et une sauce au choix', 8.20,
    (SELECT id FROM produit WHERE nom = 'Le Veggie'), 'menu-veggie.png', 1);

-- ---------------------------------------------------------------------------
-- Utilisateurs de test — un par role
-- Mot de passe en clair pour TOUS les comptes de demo : Wacdo2026!
-- (hash genere avec password_hash() — a ne jamais committer avec un vrai mot de passe)
-- ---------------------------------------------------------------------------
INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role, actif) VALUES
  ('Admin', 'Alice', 'admin@wacdo.test', '$2y$10$7pDjU8pXmmHvzrqVv8yNCurDFwKQW4Be4/fvXT.SWpGfp6pMh4yra', 'administration', 1),
  ('Prepa', 'Paul', 'preparation@wacdo.test', '$2y$10$7pDjU8pXmmHvzrqVv8yNCurDFwKQW4Be4/fvXT.SWpGfp6pMh4yra', 'preparation', 1),
  ('Accueil', 'Amine', 'accueil@wacdo.test', '$2y$10$7pDjU8pXmmHvzrqVv8yNCurDFwKQW4Be4/fvXT.SWpGfp6pMh4yra', 'accueil', 1);
