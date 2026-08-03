/**
 * WacdoRender — construction du DOM (T02.7, T02.12, T02.14).
 * Construit les éléments via createElement/textContent plutôt que des
 * gabarits innerHTML bruts, pour ne jamais interpréter du texte comme du HTML.
 */
const WacdoRender = (function () {
  function formaterPrix(montant) {
    return montant.toFixed(2).replace('.', ',') + '\u00A0€';
  }

  function creerElement(balise, classe, texte) {
    const element = document.createElement(balise);
    if (classe) element.className = classe;
    if (texte !== undefined) element.textContent = texte;
    return element;
  }

  function creerCarteProduit(produit) {
    const carte = creerElement('article', 'carte-produit');
    carte.dataset.categorie = produit.categorie;

    const image = document.createElement('img');
    image.src = 'img/' + produit.image;
    image.alt = produit.nom;
    image.loading = 'lazy';
    carte.appendChild(image);

    const corps = creerElement('div', 'carte-produit__corps');
    corps.appendChild(creerElement('h3', null, produit.nom));
    corps.appendChild(creerElement('p', 'carte-produit__description', produit.description));

    const prixAffiche = typeof produit.prix === 'number'
      ? formaterPrix(produit.prix)
      : formaterPrix(produit.prix.petite) + ' – ' + formaterPrix(produit.prix.grande);
    corps.appendChild(creerElement('p', 'carte-produit__prix', prixAffiche));

    const bouton = creerElement('button', 'btn btn-primaire btn-block', produit.disponible ? 'Ajouter' : 'Indisponible');
    bouton.type = 'button';
    bouton.dataset.action = 'ajouter-produit';
    bouton.dataset.produitId = produit.id;
    bouton.disabled = !produit.disponible;
    corps.appendChild(bouton);

    carte.appendChild(corps);

    if (!produit.disponible) {
      carte.classList.add('carte-produit--indisponible');
    }

    return carte;
  }

  function creerCarteMenu(menu, burger) {
    const carte = creerElement('article', 'carte-produit carte-produit--menu');
    carte.dataset.categorie = 'menu';

    const image = document.createElement('img');
    image.src = 'img/' + menu.image;
    image.alt = menu.nom;
    image.loading = 'lazy';
    carte.appendChild(image);

    const corps = creerElement('div', 'carte-produit__corps');
    corps.appendChild(creerElement('h3', null, menu.nom));
    corps.appendChild(creerElement('p', 'carte-produit__description', menu.description));
    corps.appendChild(creerElement('p', 'carte-produit__prix', 'à partir de ' + formaterPrix(menu.prixBase)));

    const disponible = menu.disponible && burger && burger.disponible;
    const bouton = creerElement('button', 'btn btn-primaire btn-block', disponible ? 'Composer' : 'Indisponible');
    bouton.type = 'button';
    bouton.dataset.action = 'composer-menu';
    bouton.dataset.menuId = menu.id;
    bouton.disabled = !disponible;
    corps.appendChild(bouton);

    carte.appendChild(corps);

    if (!disponible) {
      carte.classList.add('carte-produit--indisponible');
    }

    return carte;
  }

  function afficherCatalogue(conteneur, produits, menus, filtreActif) {
    conteneur.innerHTML = '';
    const cartes = [];

    if (filtreActif === 'tous' || filtreActif === 'menu') {
      menus.menus.forEach((menu) => {
        const burger = produits.find((p) => p.id === menu.burgerId);
        cartes.push(creerCarteMenu(menu, burger));
      });
    }

    produits
      .filter((produit) => filtreActif === 'tous' || produit.categorie === filtreActif)
      .forEach((produit) => cartes.push(creerCarteProduit(produit)));

    if (cartes.length === 0) {
      conteneur.appendChild(creerElement('p', 'etat-vide', 'Aucun produit dans cette catégorie pour le moment.'));
      return;
    }

    cartes.forEach((carte) => conteneur.appendChild(carte));
  }

  function creerLignePanier(ligne) {
    const div = creerElement('div', 'ligne-panier');

    const image = document.createElement('img');
    image.src = 'img/' + ligne.image;
    image.alt = ligne.nom;
    div.appendChild(image);

    const infos = creerElement('div');
    infos.appendChild(creerElement('p', 'ligne-panier__nom', ligne.nom));

    const details = ligne.type === 'produit'
      ? (ligne.tailleLabel ? 'Taille ' + ligne.tailleLabel : '')
      : ligne.compositionAffichage;
    if (details) {
      infos.appendChild(creerElement('p', 'ligne-panier__details', details));
    }
    div.appendChild(infos);

    const stepper = creerElement('div', 'stepper');
    const boutonMoins = creerElement('button', null, '−');
    boutonMoins.type = 'button';
    boutonMoins.dataset.action = 'quantite-moins';
    boutonMoins.dataset.ligneId = ligne.id;
    boutonMoins.setAttribute('aria-label', 'Diminuer la quantité de ' + ligne.nom);
    stepper.appendChild(boutonMoins);

    stepper.appendChild(creerElement('span', null, String(ligne.quantite)));

    const boutonPlus = creerElement('button', null, '+');
    boutonPlus.type = 'button';
    boutonPlus.dataset.action = 'quantite-plus';
    boutonPlus.dataset.ligneId = ligne.id;
    boutonPlus.setAttribute('aria-label', 'Augmenter la quantité de ' + ligne.nom);
    stepper.appendChild(boutonPlus);
    div.appendChild(stepper);

    const colonneDroite = creerElement('div');
    colonneDroite.appendChild(creerElement('p', 'ligne-panier__prix', formaterPrix(WacdoPanier.totalLigne(ligne))));
    const boutonSupprimer = creerElement('button', 'bouton-supprimer', 'Supprimer');
    boutonSupprimer.type = 'button';
    boutonSupprimer.dataset.action = 'supprimer-ligne';
    boutonSupprimer.dataset.ligneId = ligne.id;
    colonneDroite.appendChild(boutonSupprimer);
    div.appendChild(colonneDroite);

    return div;
  }

  function afficherPanier(conteneur, lignes) {
    conteneur.innerHTML = '';

    if (lignes.length === 0) {
      conteneur.appendChild(creerElement('p', 'etat-vide', 'Votre panier est vide. Retournez à la carte pour ajouter des produits.'));
      return;
    }

    lignes.forEach((ligne) => conteneur.appendChild(creerLignePanier(ligne)));

    const total = creerElement('div', 'panier-total');
    total.appendChild(creerElement('span', null, 'Total'));
    total.appendChild(creerElement('span', null, formaterPrix(WacdoPanier.totalPanier())));
    conteneur.appendChild(total);
  }

  function afficherRecap(conteneur, lignes, numero) {
    conteneur.innerHTML = '';

    conteneur.appendChild(creerElement('p', null, 'Numéro de commande : ' + numero));

    lignes.forEach((ligne) => {
      const div = creerElement('div', 'recap-ligne');
      const details = ligne.type === 'produit'
        ? (ligne.tailleLabel ? ' (' + ligne.tailleLabel + ')' : '')
        : ' — ' + ligne.compositionAffichage;
      div.appendChild(creerElement('span', null, ligne.quantite + ' × ' + ligne.nom + details));
      div.appendChild(creerElement('span', null, formaterPrix(WacdoPanier.totalLigne(ligne))));
      conteneur.appendChild(div);
    });

    const total = creerElement('div', 'panier-total');
    total.appendChild(creerElement('span', null, 'Total à régler au comptoir'));
    total.appendChild(creerElement('span', null, formaterPrix(WacdoPanier.totalPanier())));
    conteneur.appendChild(total);
  }

  function mettreAJourBadgePanier(nbArticlesEl, totalEl) {
    nbArticlesEl.textContent = String(WacdoPanier.nombreArticles());
    totalEl.textContent = formaterPrix(WacdoPanier.totalPanier());
  }

  return {
    formaterPrix,
    afficherCatalogue,
    afficherPanier,
    afficherRecap,
    mettreAJourBadgePanier,
  };
})();
