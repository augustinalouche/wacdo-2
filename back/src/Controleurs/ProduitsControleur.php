<?php

declare(strict_types=1);

namespace Wacdo\Controleurs;

use RuntimeException;
use Wacdo\Core\Controleur;
use Wacdo\Core\Flash;
use Wacdo\Core\TeleversementImage;
use Wacdo\Core\Validation;
use Wacdo\Modeles\Produit;
use Wacdo\Modeles\ProduitDepot;
use Wacdo\Modeles\ReferentielDepot;
use Wacdo\Securite\Auth;
use Wacdo\Securite\Csrf;

/**
 * Gestion des produits (T05.1 à T05.5, T05.9) — réservée au rôle Administration.
 */
final class ProduitsControleur extends Controleur
{
    public function liste(): void
    {
        Auth::exigerModule('produits');

        $this->afficherVue('produits/liste', [
            'produits' => (new ProduitDepot())->trouverTous(),
            'message'  => Flash::consommer(),
        ]);
    }

    public function nouveauFormulaire(): void
    {
        Auth::exigerModule('produits');

        $this->afficherFormulaire(null, [], []);
    }

    public function creer(): void
    {
        Auth::exigerModule('produits');
        $this->traiterFormulaire(null);
    }

    public function editerFormulaire(string $id): void
    {
        Auth::exigerModule('produits');

        $produit = (new ProduitDepot())->trouverParId((int) $id);
        if ($produit === null) {
            $this->page404();

            return;
        }

        $this->afficherFormulaire($produit, [], []);
    }

    public function modifier(string $id): void
    {
        Auth::exigerModule('produits');

        $produit = (new ProduitDepot())->trouverParId((int) $id);
        if ($produit === null) {
            $this->page404();

            return;
        }

        $this->traiterFormulaire($produit);
    }

    public function basculerDisponibilite(string $id): void
    {
        Auth::exigerModule('produits');

        if (Csrf::estValide($_POST['jeton_csrf'] ?? null)) {
            (new ProduitDepot())->basculerDisponibilite((int) $id);
            Flash::definir('Disponibilité mise à jour.');
        }

        $this->redirigerVers('/produits');
    }

    public function supprimer(string $id): void
    {
        Auth::exigerModule('produits');

        if (Csrf::estValide($_POST['jeton_csrf'] ?? null)) {
            $resultat = (new ProduitDepot())->supprimer((int) $id);
            Flash::definir($resultat === true ? 'Produit supprimé.' : (string) $resultat);
        }

        $this->redirigerVers('/produits');
    }

    /**
     * @param array<int, string> $erreurs
     * @param array<string, mixed> $valeursSoumises
     */
    private function afficherFormulaire(?Produit $produit, array $erreurs, array $valeursSoumises): void
    {
        $referentiel = new ReferentielDepot();

        $this->afficherVue('produits/formulaire', [
            'produit'         => $produit,
            'categories'      => $referentiel->categories(),
            'tailles'         => $referentiel->tailles(),
            'erreurs'         => $erreurs,
            'valeursSoumises' => $valeursSoumises,
        ]);
    }

    private function traiterFormulaire(?Produit $produitExistant): void
    {
        if (!Csrf::estValide($_POST['jeton_csrf'] ?? null)) {
            $this->afficherFormulaire($produitExistant, ['Formulaire expiré, merci de réessayer.'], $_POST);

            return;
        }

        $referentiel = new ReferentielDepot();
        $categories = $referentiel->categories();
        $categorieIds = array_column($categories, 'id');

        $nom = trim((string) ($_POST['nom'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $categorieId = (int) ($_POST['categorie_id'] ?? 0);
        $disponible = ($_POST['disponible'] ?? '') === '1';
        $prixSaisi = trim((string) ($_POST['prix'] ?? ''));
        $tailleIdPetite = (int) ($_POST['taille_id_petite'] ?? 0);
        $tailleIdGrande = (int) ($_POST['taille_id_grande'] ?? 0);
        $prixPetite = trim((string) ($_POST['prix_petite'] ?? ''));
        $prixGrande = trim((string) ($_POST['prix_grande'] ?? ''));

        $categorieNom = '';
        foreach ($categories as $categorie) {
            if ($categorie['id'] === $categorieId) {
                $categorieNom = $categorie['nom'];
            }
        }
        $utiliseDesTailles = Produit::categorieUtiliseDesTailles($categorieNom);

        $validation = (new Validation())
            ->requis($nom, 'Le nom est obligatoire.')
            ->longueurMax($nom, 100, 'Le nom ne doit pas dépasser 100 caractères.')
            ->dansListe($categorieId, $categorieIds, 'Merci de choisir une catégorie valide.');

        if ($utiliseDesTailles) {
            $validation
                ->requis($prixPetite, 'Le prix (Petite taille) est obligatoire pour cette catégorie.')
                ->nombrePositif($prixPetite, 'Le prix (Petite taille) doit être un nombre positif.')
                ->requis($prixGrande, 'Le prix (Grande taille) est obligatoire pour cette catégorie.')
                ->nombrePositif($prixGrande, 'Le prix (Grande taille) doit être un nombre positif.');
        } else {
            $validation
                ->requis($prixSaisi, 'Le prix est obligatoire pour cette catégorie.')
                ->nombrePositif($prixSaisi, 'Le prix doit être un nombre positif.');
        }

        $nomImage = $produitExistant?->image;
        $fichierImage = $_FILES['image'] ?? null;
        if ($fichierImage !== null && $fichierImage['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $nomImage = TeleversementImage::enregistrer($fichierImage, $nom !== '' ? $nom : 'produit');
            } catch (RuntimeException $exception) {
                $validation->ajouterSi(true, $exception->getMessage());
            }
        }

        if (!$validation->estValide()) {
            $this->afficherFormulaire($produitExistant, $validation->erreurs(), $_POST);

            return;
        }

        $prix = $utiliseDesTailles ? null : (float) $prixSaisi;
        $depot = new ProduitDepot();

        if ($produitExistant === null) {
            $id = $depot->creer($nom, $description !== '' ? $description : null, $prix, $categorieId, $nomImage, $disponible);
        } else {
            $id = $produitExistant->id;
            $depot->modifier($id, $nom, $description !== '' ? $description : null, $prix, $categorieId, $nomImage, $disponible);
        }

        $depot->definirTailles(
            $id,
            $utiliseDesTailles ? [$tailleIdPetite => (float) $prixPetite, $tailleIdGrande => (float) $prixGrande] : []
        );

        Flash::definir($produitExistant === null ? 'Produit créé avec succès.' : 'Produit modifié avec succès.');
        $this->redirigerVers('/produits');
    }
}
