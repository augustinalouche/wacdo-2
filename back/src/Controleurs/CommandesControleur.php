<?php

declare(strict_types=1);

namespace Wacdo\Controleurs;

use RuntimeException;
use Wacdo\Core\Controleur;
use Wacdo\Core\Flash;
use Wacdo\Core\Validation;
use Wacdo\Modeles\CommandeDepot;
use Wacdo\Modeles\MenuDepot;
use Wacdo\Modeles\ProduitDepot;
use Wacdo\Modeles\ReferentielDepot;
use Wacdo\Securite\Auth;
use Wacdo\Securite\Csrf;

/**
 * Gestion des commandes (`EPIC 7`) : saisie comptoir/téléphone (Accueil),
 * suivi de préparation (Préparation) et de remise au client (Accueil),
 * historique consultable par les trois rôles du module `commandes`.
 */
final class CommandesControleur extends Controleur
{
    private const ROLES_SAISIE = ['accueil', 'administration'];
    private const ROLES_PREPARATION = ['preparation', 'administration'];
    private const ROLES_REMISE = ['accueil', 'administration'];

    public function liste(): void
    {
        Auth::exigerModule('commandes');

        $role = (string) (Auth::utilisateurConnecte()['role'] ?? '');
        $depot = new CommandeDepot();

        $statutFiltre = trim((string) ($_GET['statut'] ?? ''));
        $dateDebut = trim((string) ($_GET['date_debut'] ?? ''));
        $dateFin = trim((string) ($_GET['date_fin'] ?? ''));
        $aucunFiltre = $statutFiltre === '' && $dateDebut === '' && $dateFin === '';

        if ($aucunFiltre && $role === 'preparation') {
            $commandes = $depot->trouverParStatuts(['En attente']);
        } elseif ($aucunFiltre && $role === 'accueil') {
            $commandes = $depot->trouverParStatuts(['Prete']);
        } else {
            $commandes = $depot->trouverToutes(
                $statutFiltre !== '' ? $statutFiltre : null,
                $dateDebut !== '' ? $dateDebut : null,
                $dateFin !== '' ? $dateFin : null,
            );
        }

        $this->afficherVue('commandes/liste', [
            'commandes'       => $commandes,
            'role'            => $role,
            'statuts'         => (new ReferentielDepot())->statuts(),
            'filtres'         => ['statut' => $statutFiltre, 'dateDebut' => $dateDebut, 'dateFin' => $dateFin],
            'vueParDefaut'    => $aucunFiltre,
            'peutSaisir'      => in_array($role, self::ROLES_SAISIE, true),
            'peutPreparer'    => in_array($role, self::ROLES_PREPARATION, true),
            'peutRemettre'    => in_array($role, self::ROLES_REMISE, true),
            'message'         => Flash::consommer(),
        ]);
    }

    public function nouveauFormulaire(): void
    {
        Auth::exigerModule('commandes');
        $this->exigerRole(self::ROLES_SAISIE);

        $this->afficherFormulaireNouvelleCommande([], []);
    }

    public function creer(): void
    {
        Auth::exigerModule('commandes');
        $this->exigerRole(self::ROLES_SAISIE);

        if (!Csrf::estValide($_POST['jeton_csrf'] ?? null)) {
            $this->afficherFormulaireNouvelleCommande(['Formulaire expiré, merci de réessayer.'], $_POST);

            return;
        }

        $numero = trim((string) ($_POST['numero'] ?? ''));
        $origine = (string) ($_POST['origine'] ?? '');
        [$lignes, $erreursLignes] = $this->construireLignesDepuisFormulaire($_POST);

        $validation = (new Validation())
            ->requis($numero, 'Le numéro de commande est obligatoire.')
            ->longueurMax($numero, 20, 'Le numéro de commande est trop long (20 caractères maximum).')
            ->dansListe($origine, ['comptoir', 'telephone'], 'Merci de choisir une origine de commande valide.')
            ->ajouterSi($lignes === [] && $erreursLignes === [], 'La commande doit contenir au moins un produit ou un menu.');

        foreach ($erreursLignes as $erreurLigne) {
            $validation->ajouterSi(true, $erreurLigne);
        }

        if (!$validation->estValide()) {
            $this->afficherFormulaireNouvelleCommande($validation->erreurs(), $_POST);

            return;
        }

        $utilisateurId = (int) (Auth::utilisateurConnecte()['id'] ?? 0);

        try {
            (new CommandeDepot())->creer($numero, $origine, $lignes, $utilisateurId > 0 ? $utilisateurId : null);
        } catch (RuntimeException $exception) {
            $this->afficherFormulaireNouvelleCommande([$exception->getMessage()], $_POST);

            return;
        }

        Flash::definir("Commande n°{$numero} enregistrée avec succès.");
        $this->redirigerVers('/commandes');
    }

    public function marquerPreparee(string $id): void
    {
        Auth::exigerModule('commandes');
        $this->exigerRole(self::ROLES_PREPARATION);

        if (Csrf::estValide($_POST['jeton_csrf'] ?? null)) {
            (new CommandeDepot())->changerStatut((int) $id, 'Prete');
            Flash::definir('Commande marquée comme préparée.');
        }

        $this->redirigerVers('/commandes');
    }

    public function marquerLivree(string $id): void
    {
        Auth::exigerModule('commandes');
        $this->exigerRole(self::ROLES_REMISE);

        if (Csrf::estValide($_POST['jeton_csrf'] ?? null)) {
            (new CommandeDepot())->changerStatut((int) $id, 'Livree');
            Flash::definir('Commande marquée comme livrée.');
        }

        $this->redirigerVers('/commandes');
    }

    /**
     * @param array<int, string> $rolesAutorises
     */
    private function exigerRole(array $rolesAutorises): void
    {
        $role = (string) (Auth::utilisateurConnecte()['role'] ?? '');
        if (!in_array($role, $rolesAutorises, true)) {
            $this->page403();
        }
    }

    /**
     * Traduit les champs bruts du formulaire de saisie (T07.2) en lignes
     * normalisées, conformes à la structure `docs/conception/04-structure-json.md`.
     *
     * @param array<string, mixed> $post
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>}
     */
    private function construireLignesDepuisFormulaire(array $post): array
    {
        $lignes = [];
        $erreurs = [];

        foreach ((array) ($post['produits'] ?? []) as $produitId => $quantite) {
            $quantite = (int) $quantite;
            if ($quantite <= 0) {
                continue;
            }
            $lignes[] = ['type' => 'produit', 'produitId' => (int) $produitId, 'tailleId' => null, 'quantite' => $quantite];
        }

        foreach ((array) ($post['produitsTaille'] ?? []) as $produitId => $quantitesParTaille) {
            foreach ((array) $quantitesParTaille as $tailleId => $quantite) {
                $quantite = (int) $quantite;
                if ($quantite <= 0) {
                    continue;
                }
                $lignes[] = ['type' => 'produit', 'produitId' => (int) $produitId, 'tailleId' => (int) $tailleId, 'quantite' => $quantite];
            }
        }

        foreach ((array) ($post['menus'] ?? []) as $menuId => $quantite) {
            $quantite = (int) $quantite;
            if ($quantite <= 0) {
                continue;
            }

            $composition = (array) ($post['menuComposition'][$menuId] ?? []);
            $champsRequis = ['accompagnementProduitId', 'accompagnementTailleId', 'boissonProduitId', 'boissonTailleId', 'sauceId'];
            $compositionComplete = [];
            $incomplet = false;

            foreach ($champsRequis as $champ) {
                $valeur = $composition[$champ] ?? '';
                if ($valeur === '') {
                    $incomplet = true;

                    break;
                }
                $compositionComplete[$champ] = (int) $valeur;
            }

            if ($incomplet) {
                $erreurs[] = 'Merci de compléter la composition (accompagnement, boisson, sauce) de chaque menu commandé.';

                continue;
            }

            $lignes[] = ['type' => 'menu', 'menuId' => (int) $menuId, 'quantite' => $quantite, 'composition' => $compositionComplete];
        }

        return [$lignes, $erreurs];
    }

    /**
     * @param array<int, string> $erreurs
     * @param array<string, mixed> $valeursSoumises
     */
    private function afficherFormulaireNouvelleCommande(array $erreurs, array $valeursSoumises): void
    {
        $produitsDisponibles = array_values(array_filter(
            (new ProduitDepot())->trouverTous(),
            static fn ($produit) => $produit->disponible
        ));
        $menusDisponibles = array_values(array_filter(
            (new MenuDepot())->trouverTous(),
            static fn ($menu) => $menu->disponible
        ));
        $referentiel = new ReferentielDepot();

        $this->afficherVue('commandes/nouvelle', [
            'produitsSansTaille' => array_values(array_filter($produitsDisponibles, static fn ($p) => !$p->utiliseDesTailles())),
            'produitsAvecTaille' => array_values(array_filter($produitsDisponibles, static fn ($p) => $p->utiliseDesTailles())),
            'menus'              => $menusDisponibles,
            'tailles'            => $referentiel->tailles(),
            'sauces'             => $referentiel->sauces(),
            'erreurs'            => $erreurs,
            'valeursSoumises'    => $valeursSoumises,
        ]);
    }
}
