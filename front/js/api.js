/**
 * WacdoApi — accès à l'API réelle du back-office (T02.5, T02.6, T02.15, T10.1).
 * Les fichiers JSON statiques (data/produits.json, data/menus.json) ont servi
 * de maquette de données pendant l'EPIC 2 ; ce module consomme maintenant
 * l'API PHP (EPIC 8), qui renvoie exactement la même structure
 * (voir docs/conception/04-structure-json.md) — aucun autre fichier n'a eu
 * besoin de changer pour ce basculement.
 */
const WacdoApi = (function () {
  const URL_API_BASE = '/wacdo2/back/api';

  async function chargerProduits() {
    const reponse = await fetch(`${URL_API_BASE}/produits`, { cache: 'no-store' });
    if (!reponse.ok) {
      throw new Error('Impossible de charger les produits (HTTP ' + reponse.status + ')');
    }
    return reponse.json();
  }

  async function chargerMenus() {
    const reponse = await fetch(`${URL_API_BASE}/menus`, { cache: 'no-store' });
    if (!reponse.ok) {
      throw new Error('Impossible de charger les menus (HTTP ' + reponse.status + ')');
    }
    return reponse.json();
  }

  /**
   * Envoie la commande à l'API réelle (T08.3) et renvoie l'accusé de
   * réception (T08.6). En cas d'erreur (validation, produit indisponible,
   * réseau…), le message structuré renvoyé par l'API (`erreur`) est propagé
   * pour être affiché tel quel côté borne.
   */
  async function envoyerCommande(commande) {
    const reponse = await fetch(`${URL_API_BASE}/commandes`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(commande),
    });

    const corps = await reponse.json().catch(() => null);

    if (!reponse.ok || !corps || !corps.succes) {
      throw new Error((corps && corps.erreur) || 'HTTP ' + reponse.status);
    }

    return corps;
  }

  return {
    chargerProduits,
    chargerMenus,
    envoyerCommande,
  };
})();
