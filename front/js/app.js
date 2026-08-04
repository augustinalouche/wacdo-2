/**
 * WacdoApp — orchestration : navigation entre écrans, formulaires,
 * modales de composition, gestion des erreurs, mise à jour du badge panier
 * (T02.13 à T02.17).
 */
const WacdoApp = (function () {
  let produits = [];
  let menus = { sauces: [], menus: [] };
  let menuEnCours = null;
  let produitTailleEnCours = null;

  const els = {};

  function qs(id) {
    return document.getElementById(id);
  }

  function cacherReferences() {
    els.grilleProduits = qs('grille-produits');
    els.messageCatalogue = qs('message-catalogue');
    els.boutonPanier = qs('bouton-panier');
    els.panierContenu = qs('panier-contenu');
    els.boutonValiderPanier = qs('bouton-valider-panier');
    els.panierNbArticles = qs('panier-nb-articles');
    els.panierTotal = qs('panier-total');
    els.formulaireNumero = qs('formulaire-numero');
    els.champNumero = qs('champ-numero');
    els.erreurNumero = qs('erreur-numero');
    els.recapContenu = qs('recap-contenu');
    els.messageEnvoi = qs('message-envoi');
    els.boutonConfirmerCommande = qs('bouton-confirmer-commande');
    els.confirmationNumero = qs('confirmation-numero');
    els.modaleMenu = qs('modale-menu');
    els.modaleMenuContenu = qs('modale-menu-contenu');
    els.formulaireMenu = qs('formulaire-menu');
    els.erreurModaleMenu = qs('erreur-modale-menu');
    els.modaleTaille = qs('modale-taille');
    els.modaleTailleContenu = qs('modale-taille-contenu');
    els.formulaireTaille = qs('formulaire-taille');
  }

  function majBadgePanier() {
    WacdoRender.mettreAJourBadgePanier(els.panierNbArticles, els.panierTotal);
  }

  function afficherEcran(id) {
    document.querySelectorAll('.ecran').forEach((ecran) => {
      ecran.hidden = ecran.id !== id;
    });
    const titre = qs(id).querySelector('h1');
    if (titre) {
      titre.setAttribute('tabindex', '-1');
      titre.focus();
    }
    window.scrollTo(0, 0);
  }

  function filtreActif() {
    const bouton = document.querySelector('.filtre.est-actif');
    return bouton ? bouton.dataset.categorie : 'tous';
  }

  async function chargerCatalogue() {
    els.messageCatalogue.hidden = true;
    try {
      const [produitsCharges, menusCharges] = await Promise.all([
        WacdoApi.chargerProduits(),
        WacdoApi.chargerMenus(),
      ]);
      produits = produitsCharges;
      menus = menusCharges;
      WacdoRender.afficherCatalogue(els.grilleProduits, produits, menus, filtreActif());
    } catch (erreur) {
      console.error('[WacdoApp] chargement catalogue', erreur);
      els.grilleProduits.innerHTML = '';
      els.messageCatalogue.hidden = false;
      els.messageCatalogue.textContent = 'Impossible de charger la carte pour le moment. Vérifiez votre connexion et réessayez.';
    }
  }

  function gererAjoutProduit(produit) {
    if (!produit || !produit.disponible) {
      return;
    }

    if (typeof produit.prix === 'number') {
      WacdoPanier.ajouterProduitSimple(produit, null, null, produit.prix, 1);
      majBadgePanier();
      return;
    }

    ouvrirModaleTaille(produit);
  }

  function ouvrirModaleTaille(produit) {
    produitTailleEnCours = produit;
    els.modaleTailleContenu.innerHTML = '';

    const fieldset = document.createElement('fieldset');
    const legend = document.createElement('legend');
    legend.textContent = produit.nom;
    fieldset.appendChild(legend);

    ['petite', 'grande'].forEach((cle, index) => {
      const id = 'taille-' + cle;
      const label = document.createElement('label');
      label.className = 'option-radio';
      label.setAttribute('for', id);

      const input = document.createElement('input');
      input.type = 'radio';
      input.name = 'taille';
      input.id = id;
      input.value = cle;
      input.checked = index === 0;

      label.appendChild(input);
      label.append((cle === 'petite' ? 'Petite' : 'Grande') + ' — ' + WacdoRender.formaterPrix(produit.prix[cle]));
      fieldset.appendChild(label);
    });

    els.modaleTailleContenu.appendChild(fieldset);
    els.modaleTaille.showModal();
  }

  function validerEtAjouterTaille() {
    const choix = els.formulaireTaille.querySelector('input[name="taille"]:checked');
    if (!choix || !produitTailleEnCours) {
      return;
    }

    const tailleLabel = choix.value === 'petite' ? 'Petite' : 'Grande';
    // 1 = Petite, 2 = Grande : correspond aux id de la table `taille` (back/sql/seed.sql).
    const tailleId = choix.value === 'petite' ? 1 : 2;
    const prix = produitTailleEnCours.prix[choix.value];

    WacdoPanier.ajouterProduitSimple(produitTailleEnCours, tailleId, tailleLabel, prix, 1);
    majBadgePanier();
    els.modaleTaille.close();
    produitTailleEnCours = null;
  }

  function creerGroupeChoixProduit(nomGroupe, titre, produitsDisponibles) {
    const fieldset = document.createElement('fieldset');
    const legend = document.createElement('legend');
    legend.textContent = titre;
    fieldset.appendChild(legend);

    produitsDisponibles.forEach((produit, indexProduit) => {
      ['petite', 'grande'].forEach((taille, indexTaille) => {
        const id = nomGroupe + '-' + produit.id + '-' + taille;
        const label = document.createElement('label');
        label.className = 'option-radio';
        label.setAttribute('for', id);

        const input = document.createElement('input');
        input.type = 'radio';
        input.name = nomGroupe;
        input.id = id;
        input.value = produit.id + ':' + taille;
        input.checked = indexProduit === 0 && indexTaille === 0;

        label.appendChild(input);
        const supplement = taille === 'grande' ? ' (+0,50 €)' : '';
        label.append(produit.nom + ' — ' + (taille === 'petite' ? 'Petite' : 'Grande') + supplement);
        fieldset.appendChild(label);
      });
    });

    return fieldset;
  }

  function creerGroupeChoixSauce() {
    const fieldset = document.createElement('fieldset');
    const legend = document.createElement('legend');
    legend.textContent = 'Sauce';
    fieldset.appendChild(legend);

    menus.sauces.forEach((sauce, index) => {
      const id = 'sauce-' + sauce.id;
      const label = document.createElement('label');
      label.className = 'option-radio';
      label.setAttribute('for', id);

      const input = document.createElement('input');
      input.type = 'radio';
      input.name = 'sauce';
      input.id = id;
      input.value = sauce.id;
      input.checked = index === 0;

      label.appendChild(input);
      label.append(sauce.nom);
      fieldset.appendChild(label);
    });

    return fieldset;
  }

  function ouvrirModaleMenu(menu) {
    if (!menu) {
      return;
    }

    menuEnCours = menu;
    els.erreurModaleMenu.hidden = true;
    els.modaleMenuContenu.innerHTML = '';

    const accompagnements = produits.filter((p) => p.categorie === 'accompagnement' && p.disponible);
    const boissons = produits.filter((p) => p.categorie === 'boisson' && p.disponible);

    els.modaleMenuContenu.appendChild(creerGroupeChoixProduit('accompagnement', 'Accompagnement', accompagnements));
    els.modaleMenuContenu.appendChild(creerGroupeChoixProduit('boisson', 'Boisson', boissons));
    els.modaleMenuContenu.appendChild(creerGroupeChoixSauce());

    els.modaleMenu.showModal();
  }

  function validerEtAjouterMenu() {
    const accompagnementChoisi = els.formulaireMenu.querySelector('input[name="accompagnement"]:checked');
    const boissonChoisie = els.formulaireMenu.querySelector('input[name="boisson"]:checked');
    const sauceChoisie = els.formulaireMenu.querySelector('input[name="sauce"]:checked');

    if (!accompagnementChoisi || !boissonChoisie || !sauceChoisie || !menuEnCours) {
      els.erreurModaleMenu.hidden = false;
      els.erreurModaleMenu.textContent = 'Merci de faire un choix pour chaque option.';
      return;
    }

    const [accompagnementId, accompagnementTaille] = accompagnementChoisi.value.split(':');
    const [boissonId, boissonTaille] = boissonChoisie.value.split(':');

    const accompagnement = produits.find((p) => p.id === Number(accompagnementId));
    const boisson = produits.find((p) => p.id === Number(boissonId));
    const sauce = menus.sauces.find((s) => s.id === Number(sauceChoisie.value));

    const supplementAccompagnement = accompagnementTaille === 'grande' ? 0.5 : 0;
    const supplementBoisson = boissonTaille === 'grande' ? 0.5 : 0;
    const prixUnitaire = Math.round((menuEnCours.prixBase + supplementAccompagnement + supplementBoisson) * 100) / 100;

    const composition = {
      accompagnementProduitId: accompagnement.id,
      accompagnementTailleId: accompagnementTaille === 'petite' ? 1 : 2,
      boissonProduitId: boisson.id,
      boissonTailleId: boissonTaille === 'petite' ? 1 : 2,
      sauceId: sauce.id,
    };

    const compositionAffichage = accompagnement.nom + ' (' + (accompagnementTaille === 'petite' ? 'Petite' : 'Grande') + '), '
      + boisson.nom + ' (' + (boissonTaille === 'petite' ? 'Petite' : 'Grande') + '), '
      + sauce.nom;

    WacdoPanier.ajouterMenu(menuEnCours, composition, compositionAffichage, prixUnitaire, 1);
    majBadgePanier();
    els.modaleMenu.close();
    menuEnCours = null;
  }

  function actualiserVuePanier() {
    WacdoRender.afficherPanier(els.panierContenu, WacdoPanier.obtenirLignes());
    els.boutonValiderPanier.disabled = WacdoPanier.estVide();
  }

  function attacherEvenements() {
    document.querySelectorAll('.filtre').forEach((bouton) => {
      bouton.addEventListener('click', () => {
        document.querySelectorAll('.filtre').forEach((b) => {
          b.classList.remove('est-actif');
          b.setAttribute('aria-pressed', 'false');
        });
        bouton.classList.add('est-actif');
        bouton.setAttribute('aria-pressed', 'true');
        WacdoRender.afficherCatalogue(els.grilleProduits, produits, menus, bouton.dataset.categorie);
      });
    });

    els.grilleProduits.addEventListener('click', (evenement) => {
      const bouton = evenement.target.closest('button[data-action]');
      if (!bouton) {
        return;
      }

      if (bouton.dataset.action === 'ajouter-produit') {
        gererAjoutProduit(produits.find((p) => p.id === Number(bouton.dataset.produitId)));
      }

      if (bouton.dataset.action === 'composer-menu') {
        ouvrirModaleMenu(menus.menus.find((m) => m.id === Number(bouton.dataset.menuId)));
      }
    });

    els.boutonPanier.addEventListener('click', () => {
      actualiserVuePanier();
      afficherEcran('ecran-panier');
    });

    document.querySelectorAll('[data-action="retour-catalogue"]').forEach((b) => {
      b.addEventListener('click', () => afficherEcran('ecran-catalogue'));
    });
    document.querySelectorAll('[data-action="retour-panier"]').forEach((b) => {
      b.addEventListener('click', () => {
        actualiserVuePanier();
        afficherEcran('ecran-panier');
      });
    });
    document.querySelectorAll('[data-action="retour-numero"]').forEach((b) => {
      b.addEventListener('click', () => afficherEcran('ecran-numero'));
    });
    document.querySelectorAll('[data-action="nouvelle-commande"]').forEach((b) => {
      b.addEventListener('click', () => {
        WacdoPanier.vider();
        majBadgePanier();
        els.champNumero.value = '';
        afficherEcran('ecran-catalogue');
      });
    });
    document.querySelectorAll('[data-action="fermer-modale"]').forEach((b) => {
      b.addEventListener('click', () => b.closest('dialog').close());
    });

    els.panierContenu.addEventListener('click', (evenement) => {
      const bouton = evenement.target.closest('button[data-action]');
      if (!bouton) {
        return;
      }

      const id = Number(bouton.dataset.ligneId);
      if (bouton.dataset.action === 'quantite-plus') WacdoPanier.modifierQuantite(id, 1);
      if (bouton.dataset.action === 'quantite-moins') WacdoPanier.modifierQuantite(id, -1);
      if (bouton.dataset.action === 'supprimer-ligne') WacdoPanier.supprimerLigne(id);

      actualiserVuePanier();
      majBadgePanier();
    });

    els.boutonValiderPanier.addEventListener('click', () => {
      if (!WacdoPanier.estVide()) {
        afficherEcran('ecran-numero');
      }
    });

    els.formulaireNumero.addEventListener('submit', (evenement) => {
      evenement.preventDefault();
      const valeur = els.champNumero.value.trim();
      const nombre = Number(valeur);

      if (!valeur || !Number.isInteger(nombre) || nombre < 1 || nombre > 999) {
        els.erreurNumero.hidden = false;
        els.erreurNumero.textContent = 'Merci de saisir un numéro entier entre 1 et 999.';
        els.champNumero.setAttribute('aria-invalid', 'true');
        els.champNumero.focus();
        return;
      }

      els.erreurNumero.hidden = true;
      els.champNumero.removeAttribute('aria-invalid');
      WacdoRender.afficherRecap(els.recapContenu, WacdoPanier.obtenirLignes(), nombre);
      afficherEcran('ecran-recap');
    });

    els.boutonConfirmerCommande.addEventListener('click', async () => {
      els.messageEnvoi.hidden = true;
      els.boutonConfirmerCommande.disabled = true;
      els.boutonConfirmerCommande.textContent = 'Envoi en cours…';

      try {
        const commande = WacdoPanier.construireJsonCommande(els.champNumero.value.trim());
        const reponse = await WacdoApi.envoyerCommande(commande);

        els.confirmationNumero.textContent = reponse.numero;
        WacdoPanier.vider();
        majBadgePanier();
        afficherEcran('ecran-confirmation');
      } catch (erreur) {
        console.error('[WacdoApp] envoi commande', erreur);
        els.messageEnvoi.hidden = false;
        els.messageEnvoi.textContent = erreur.message || "L'envoi de la commande a échoué. Merci de réessayer.";
      } finally {
        els.boutonConfirmerCommande.disabled = false;
        els.boutonConfirmerCommande.textContent = 'Confirmer la commande';
      }
    });

    els.formulaireMenu.addEventListener('submit', (evenement) => {
      evenement.preventDefault();
      validerEtAjouterMenu();
    });

    els.formulaireTaille.addEventListener('submit', (evenement) => {
      evenement.preventDefault();
      validerEtAjouterTaille();
    });
  }

  async function init() {
    cacherReferences();
    attacherEvenements();
    await chargerCatalogue();
  }

  return { init };
})();

document.addEventListener('DOMContentLoaded', WacdoApp.init);
