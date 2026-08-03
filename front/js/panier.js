/**
 * WacdoPanier — état et logique du panier (T02.8 à T02.12, T02.15).
 * Chaque ligne stocke déjà tout ce qu'il faut pour l'affichage (nom, image,
 * détails de taille/composition) afin que le rendu n'ait pas besoin de
 * ré-interroger le catalogue.
 */
const WacdoPanier = (function () {
  let lignes = [];
  let prochainId = 1;

  function cleSignature(ligne) {
    return ligne.type === 'produit'
      ? 'produit:' + ligne.produitId + ':' + (ligne.tailleId ?? 'aucune')
      : 'menu:' + ligne.menuId + ':' + JSON.stringify(ligne.composition);
  }

  function ajouterLigne(ligne) {
    const signature = cleSignature(ligne);
    const existante = lignes.find((l) => cleSignature(l) === signature);

    if (existante) {
      existante.quantite += ligne.quantite;
      return existante;
    }

    const nouvelleLigne = { ...ligne, id: prochainId++ };
    lignes.push(nouvelleLigne);
    return nouvelleLigne;
  }

  /**
   * @param {object} produit produit du catalogue (data/produits.json)
   * @param {number|null} tailleId id de taille (T01.4 : table `taille`), null si sans taille
   * @param {string|null} tailleLabel libellé affiché ("Petite"/"Grande"), null si sans taille
   * @param {number} prixUnitaire prix déjà calculé (supplément grande taille inclus)
   * @param {number} quantite
   */
  function ajouterProduitSimple(produit, tailleId, tailleLabel, prixUnitaire, quantite = 1) {
    return ajouterLigne({
      type: 'produit',
      produitId: produit.id,
      nom: produit.nom,
      image: produit.image,
      tailleId,
      tailleLabel,
      prixUnitaire,
      quantite,
    });
  }

  /**
   * @param {object} menu menu du catalogue (data/menus.json)
   * @param {object} composition { accompagnementProduitId, accompagnementTailleId, boissonProduitId, boissonTailleId, sauceId }
   * @param {string} compositionAffichage résumé lisible ("Frites (Grande), Cola (Petite), Ketchup")
   * @param {number} prixUnitaire prixBase + suppléments de taille
   * @param {number} quantite
   */
  function ajouterMenu(menu, composition, compositionAffichage, prixUnitaire, quantite = 1) {
    return ajouterLigne({
      type: 'menu',
      menuId: menu.id,
      nom: menu.nom,
      image: menu.image,
      composition,
      compositionAffichage,
      prixUnitaire,
      quantite,
    });
  }

  function modifierQuantite(id, delta) {
    const ligne = lignes.find((l) => l.id === id);
    if (!ligne) {
      return;
    }

    ligne.quantite += delta;

    if (ligne.quantite <= 0) {
      supprimerLigne(id);
    }
  }

  function supprimerLigne(id) {
    lignes = lignes.filter((l) => l.id !== id);
  }

  function obtenirLignes() {
    return lignes;
  }

  function estVide() {
    return lignes.length === 0;
  }

  function arrondir(montant) {
    return Math.round(montant * 100) / 100;
  }

  function totalLigne(ligne) {
    return arrondir(ligne.prixUnitaire * ligne.quantite);
  }

  function totalPanier() {
    return arrondir(lignes.reduce((somme, ligne) => somme + totalLigne(ligne), 0));
  }

  function nombreArticles() {
    return lignes.reduce((somme, ligne) => somme + ligne.quantite, 0);
  }

  function vider() {
    lignes = [];
  }

  /**
   * Construit le JSON envoyé à l'API (T02.15), conforme à
   * docs/conception/04-structure-json.md section 3. Le prix n'y figure
   * jamais : c'est au serveur de le recalculer (T09.x).
   */
  function construireJsonCommande(numero) {
    return {
      numero: String(numero),
      origine: 'borne',
      lignes: lignes.map((ligne) => (
        ligne.type === 'produit'
          ? {
            type: 'produit',
            produitId: ligne.produitId,
            tailleId: ligne.tailleId,
            quantite: ligne.quantite,
          }
          : {
            type: 'menu',
            menuId: ligne.menuId,
            quantite: ligne.quantite,
            composition: ligne.composition,
          }
      )),
    };
  }

  return {
    ajouterProduitSimple,
    ajouterMenu,
    modifierQuantite,
    supprimerLigne,
    obtenirLignes,
    estVide,
    totalLigne,
    totalPanier,
    nombreArticles,
    vider,
    construireJsonCommande,
  };
})();
