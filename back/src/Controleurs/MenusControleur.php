<?php

declare(strict_types=1);

namespace Wacdo\Controleurs;

use RuntimeException;
use Wacdo\Core\Controleur;
use Wacdo\Core\Flash;
use Wacdo\Core\TeleversementImage;
use Wacdo\Core\Validation;
use Wacdo\Modeles\Menu;
use Wacdo\Modeles\MenuDepot;
use Wacdo\Securite\Auth;
use Wacdo\Securite\Csrf;

/**
 * Gestion des menus (T05.6 à T05.9) — réservée au rôle Administration.
 */
final class MenusControleur extends Controleur
{
    public function liste(): void
    {
        Auth::exigerModule('menus');

        $this->afficherVue('menus/liste', [
            'menus'   => (new MenuDepot())->trouverTous(),
            'message' => Flash::consommer(),
        ]);
    }

    public function nouveauFormulaire(): void
    {
        Auth::exigerModule('menus');
        $this->afficherFormulaire(null, [], []);
    }

    public function creer(): void
    {
        Auth::exigerModule('menus');
        $this->traiterFormulaire(null);
    }

    public function editerFormulaire(string $id): void
    {
        Auth::exigerModule('menus');

        $menu = (new MenuDepot())->trouverParId((int) $id);
        if ($menu === null) {
            $this->page404();

            return;
        }

        $this->afficherFormulaire($menu, [], []);
    }

    public function modifier(string $id): void
    {
        Auth::exigerModule('menus');

        $menu = (new MenuDepot())->trouverParId((int) $id);
        if ($menu === null) {
            $this->page404();

            return;
        }

        $this->traiterFormulaire($menu);
    }

    public function supprimer(string $id): void
    {
        Auth::exigerModule('menus');

        if (Csrf::estValide($_POST['jeton_csrf'] ?? null)) {
            $resultat = (new MenuDepot())->supprimer((int) $id);
            Flash::definir($resultat === true ? 'Menu supprimé.' : (string) $resultat);
        }

        $this->redirigerVers('/menus');
    }

    /**
     * @param array<int, string> $erreurs
     * @param array<string, mixed> $valeursSoumises
     */
    private function afficherFormulaire(?Menu $menu, array $erreurs, array $valeursSoumises): void
    {
        $depot = new MenuDepot();

        $this->afficherVue('menus/formulaire', [
            'menu'             => $menu,
            'burgersDisponibles' => $depot->burgersDisponibles($menu?->id),
            'erreurs'          => $erreurs,
            'valeursSoumises'  => $valeursSoumises,
        ]);
    }

    private function traiterFormulaire(?Menu $menuExistant): void
    {
        if (!Csrf::estValide($_POST['jeton_csrf'] ?? null)) {
            $this->afficherFormulaire($menuExistant, ['Formulaire expiré, merci de réessayer.'], $_POST);

            return;
        }

        $depot = new MenuDepot();
        $burgersDisponibles = array_column($depot->burgersDisponibles($menuExistant?->id), 'id');

        $nom = trim((string) ($_POST['nom'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $prixBase = trim((string) ($_POST['prix_base'] ?? ''));
        $burgerId = (int) ($_POST['burger_id'] ?? 0);
        $disponible = ($_POST['disponible'] ?? '') === '1';

        $validation = (new Validation())
            ->requis($nom, 'Le nom est obligatoire.')
            ->longueurMax($nom, 100, 'Le nom ne doit pas dépasser 100 caractères.')
            ->requis($prixBase, 'Le prix de base est obligatoire.')
            ->nombrePositif($prixBase, 'Le prix de base doit être un nombre positif.')
            ->dansListe($burgerId, $burgersDisponibles, 'Merci de choisir un burger disponible (chaque burger n\'est associé qu\'à un seul menu).');

        $nomImage = $menuExistant?->image;
        $fichierImage = $_FILES['image'] ?? null;
        if ($fichierImage !== null && $fichierImage['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $nomImage = TeleversementImage::enregistrer($fichierImage, $nom !== '' ? $nom . '-menu' : 'menu');
            } catch (RuntimeException $exception) {
                $validation->ajouterSi(true, $exception->getMessage());
            }
        }

        if (!$validation->estValide()) {
            $this->afficherFormulaire($menuExistant, $validation->erreurs(), $_POST);

            return;
        }

        if ($menuExistant === null) {
            $depot->creer($nom, $description !== '' ? $description : null, (float) $prixBase, $burgerId, $nomImage, $disponible);
        } else {
            $depot->modifier($menuExistant->id, $nom, $description !== '' ? $description : null, (float) $prixBase, $burgerId, $nomImage, $disponible);
        }

        Flash::definir($menuExistant === null ? 'Menu créé avec succès.' : 'Menu modifié avec succès.');
        $this->redirigerVers('/menus');
    }
}
