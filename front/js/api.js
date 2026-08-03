/**
 * WacdoApi — accès aux données (T02.5, T02.6, T02.15).
 * Les fichiers JSON statiques (data/produits.json, data/menus.json) ont
 * exactement la même structure que les futures réponses de l'API réelle
 * (voir docs/conception/04-structure-json.md). Basculer vers l'API réelle
 * (T10.1) ne demandera que de changer les URLs ci-dessous.
 */
const WacdoApi = (function () {
  const URL_PRODUITS = 'data/produits.json';
  const URL_MENUS = 'data/menus.json';
  const URL_API_COMMANDES = '/wacdo2/back/api/commandes';

  async function chargerProduits() {
    const reponse = await fetch(URL_PRODUITS, { cache: 'no-store' });
    if (!reponse.ok) {
      throw new Error('Impossible de charger les produits (HTTP ' + reponse.status + ')');
    }
    return reponse.json();
  }

  async function chargerMenus() {
    const reponse = await fetch(URL_MENUS, { cache: 'no-store' });
    if (!reponse.ok) {
      throw new Error('Impossible de charger les menus (HTTP ' + reponse.status + ')');
    }
    return reponse.json();
  }

  /**
   * Simule une réponse d'API tant que l'API réelle (EPIC 8) n'est pas branchée.
   * Renvoie exactement la forme attendue (voir T08.6) pour que le basculement
   * vers la vraie API (T10.1) ne change rien côté appelant.
   */
  function simulerEnvoiCommande(commande) {
    return new Promise((resolve) => {
      window.setTimeout(() => {
        resolve({
          succes: true,
          commandeId: Math.floor(Math.random() * 9000) + 1000,
          numero: commande.numero,
          message: 'Commande enregistrée (mode simulation — API non branchée, voir T10.1).',
        });
      }, 400);
    });
  }

  async function envoyerCommande(commande) {
    try {
      const reponse = await fetch(URL_API_COMMANDES, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(commande),
      });

      if (!reponse.ok) {
        throw new Error('HTTP ' + reponse.status);
      }

      return await reponse.json();
    } catch (erreur) {
      console.warn('[WacdoApi] API /api/commandes indisponible, simulation locale utilisée.', erreur);
      return simulerEnvoiCommande(commande);
    }
  }

  return {
    chargerProduits,
    chargerMenus,
    envoyerCommande,
  };
})();
