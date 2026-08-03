-- Wacdo — Schema physique de la base de donnees (T01.4 / T03.1)
-- Genere a partir de docs/conception/03-mpd.md
-- Moteur cible : MySQL 8+ / MariaDB 10.4+ (support des contraintes CHECK)

-- Nom "wacdo2" (et non "wacdo") pour eviter toute collision avec une autre
-- base "wacdo" pre-existante sur le serveur MySQL (projet distinct, sans lien
-- avec cet examen).
CREATE DATABASE IF NOT EXISTS wacdo2
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE wacdo2;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Categorie
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorie (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  nom  VARCHAR(50) NOT NULL,
  UNIQUE KEY uq_categorie_nom (nom)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Produit
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS produit (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nom           VARCHAR(100) NOT NULL,
  description   TEXT NULL,
  prix          DECIMAL(6,2) NULL,
  categorie_id  INT NOT NULL,
  image         VARCHAR(255) NULL,
  disponible    TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_produit_categorie
    FOREIGN KEY (categorie_id) REFERENCES categorie(id)
) ENGINE=InnoDB;

CREATE INDEX idx_produit_categorie ON produit(categorie_id);

-- ---------------------------------------------------------------------------
-- Taille (Petite / Grande)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS taille (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  libelle     VARCHAR(20) NOT NULL,
  supplement  DECIMAL(4,2) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_taille_libelle (libelle)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Produit x Taille (prix par couple produit/taille — accompagnements, boissons)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS produit_taille (
  produit_id  INT NOT NULL,
  taille_id   INT NOT NULL,
  prix        DECIMAL(6,2) NOT NULL,
  PRIMARY KEY (produit_id, taille_id),
  CONSTRAINT fk_produit_taille_produit
    FOREIGN KEY (produit_id) REFERENCES produit(id),
  CONSTRAINT fk_produit_taille_taille
    FOREIGN KEY (taille_id) REFERENCES taille(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Sauce
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sauce (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  nom  VARCHAR(50) NOT NULL,
  UNIQUE KEY uq_sauce_nom (nom)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Menu (associe a un burger)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS menu (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  nom          VARCHAR(100) NOT NULL,
  description  TEXT NULL,
  prix_base    DECIMAL(6,2) NOT NULL,
  burger_id    INT NOT NULL,
  image        VARCHAR(255) NULL,
  disponible   TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_menu_burger (burger_id),
  CONSTRAINT fk_menu_burger
    FOREIGN KEY (burger_id) REFERENCES produit(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Statut d'une commande
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS statut (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  libelle  VARCHAR(30) NOT NULL,
  UNIQUE KEY uq_statut_libelle (libelle)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Utilisateur (back-office)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateur (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  nom            VARCHAR(50) NOT NULL,
  prenom         VARCHAR(50) NOT NULL,
  email          VARCHAR(150) NOT NULL,
  mot_de_passe   VARCHAR(255) NOT NULL,
  role           ENUM('administration', 'preparation', 'accueil') NOT NULL,
  actif          TINYINT(1) NOT NULL DEFAULT 1,
  date_creation  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_utilisateur_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Commande
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS commande (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  numero_affichage  VARCHAR(20) NOT NULL,
  date_heure        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  statut_id         INT NOT NULL,
  montant_total     DECIMAL(8,2) NOT NULL,
  origine           ENUM('borne', 'comptoir', 'telephone') NOT NULL,
  utilisateur_id    INT NULL,
  CONSTRAINT fk_commande_statut
    FOREIGN KEY (statut_id) REFERENCES statut(id),
  CONSTRAINT fk_commande_utilisateur
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id)
    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_commande_statut ON commande(statut_id);
CREATE INDEX idx_commande_date ON commande(date_heure);

-- ---------------------------------------------------------------------------
-- Ligne de commande (produit simple OU menu — jamais les deux, jamais aucun)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ligne_commande (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  commande_id    INT NOT NULL,
  produit_id     INT NULL,
  menu_id        INT NULL,
  taille_id      INT NULL,
  quantite       INT NOT NULL,
  prix_unitaire  DECIMAL(6,2) NOT NULL,
  CONSTRAINT fk_ligne_commande_commande
    FOREIGN KEY (commande_id) REFERENCES commande(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_ligne_commande_produit
    FOREIGN KEY (produit_id) REFERENCES produit(id),
  CONSTRAINT fk_ligne_commande_menu
    FOREIGN KEY (menu_id) REFERENCES menu(id),
  CONSTRAINT fk_ligne_commande_taille
    FOREIGN KEY (taille_id) REFERENCES taille(id),
  CONSTRAINT chk_ligne_commande_quantite
    CHECK (quantite > 0),
  CONSTRAINT chk_ligne_commande_exclusivite
    CHECK ((produit_id IS NULL) <> (menu_id IS NULL))
) ENGINE=InnoDB;

CREATE INDEX idx_ligne_commande_commande ON ligne_commande(commande_id);

-- ---------------------------------------------------------------------------
-- Composition d'une ligne de commande de type "menu"
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS composition_menu (
  id                          INT AUTO_INCREMENT PRIMARY KEY,
  ligne_commande_id           INT NOT NULL,
  accompagnement_produit_id   INT NOT NULL,
  accompagnement_taille_id    INT NOT NULL,
  boisson_produit_id          INT NOT NULL,
  boisson_taille_id           INT NOT NULL,
  sauce_id                    INT NOT NULL,
  UNIQUE KEY uq_composition_ligne (ligne_commande_id),
  CONSTRAINT fk_composition_ligne
    FOREIGN KEY (ligne_commande_id) REFERENCES ligne_commande(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_composition_accompagnement_produit
    FOREIGN KEY (accompagnement_produit_id) REFERENCES produit(id),
  CONSTRAINT fk_composition_accompagnement_taille
    FOREIGN KEY (accompagnement_taille_id) REFERENCES taille(id),
  CONSTRAINT fk_composition_boisson_produit
    FOREIGN KEY (boisson_produit_id) REFERENCES produit(id),
  CONSTRAINT fk_composition_boisson_taille
    FOREIGN KEY (boisson_taille_id) REFERENCES taille(id),
  CONSTRAINT fk_composition_sauce
    FOREIGN KEY (sauce_id) REFERENCES sauce(id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
