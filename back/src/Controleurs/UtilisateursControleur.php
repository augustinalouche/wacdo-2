<?php

declare(strict_types=1);

namespace Wacdo\Controleurs;

use Wacdo\Core\Controleur;
use Wacdo\Core\Flash;
use Wacdo\Core\Validation;
use Wacdo\Modeles\Utilisateur;
use Wacdo\Modeles\UtilisateurDepot;
use Wacdo\Securite\Auth;
use Wacdo\Securite\Csrf;

/**
 * Gestion des comptes utilisateurs internes (T06.1 à T06.3) — réservée au
 * rôle Administration.
 */
final class UtilisateursControleur extends Controleur
{
    private const LONGUEUR_MIN_MOT_DE_PASSE = 8;

    public function liste(): void
    {
        Auth::exigerModule('utilisateurs');

        $this->afficherVue('utilisateurs/liste', [
            'utilisateurs'      => (new UtilisateurDepot())->trouverTous(),
            'utilisateurConnecte' => Auth::utilisateurConnecte(),
            'message'           => Flash::consommer(),
        ]);
    }

    public function nouveauFormulaire(): void
    {
        Auth::exigerModule('utilisateurs');
        $this->afficherFormulaire(null, [], []);
    }

    public function creer(): void
    {
        Auth::exigerModule('utilisateurs');
        $this->traiterFormulaire(null);
    }

    public function editerFormulaire(string $id): void
    {
        Auth::exigerModule('utilisateurs');

        $utilisateur = (new UtilisateurDepot())->trouverParId((int) $id);
        if ($utilisateur === null) {
            $this->page404();

            return;
        }

        $this->afficherFormulaire($utilisateur, [], []);
    }

    public function modifier(string $id): void
    {
        Auth::exigerModule('utilisateurs');

        $utilisateur = (new UtilisateurDepot())->trouverParId((int) $id);
        if ($utilisateur === null) {
            $this->page404();

            return;
        }

        $this->traiterFormulaire($utilisateur);
    }

    public function basculerActif(string $id): void
    {
        Auth::exigerModule('utilisateurs');

        if ((int) $id === (int) (Auth::utilisateurConnecte()['id'] ?? 0)) {
            Flash::definir('Vous ne pouvez pas désactiver votre propre compte.');
            $this->redirigerVers('/utilisateurs');

            return;
        }

        if (Csrf::estValide($_POST['jeton_csrf'] ?? null)) {
            (new UtilisateurDepot())->basculerActif((int) $id);
            Flash::definir('Statut du compte mis à jour.');
        }

        $this->redirigerVers('/utilisateurs');
    }

    public function supprimer(string $id): void
    {
        Auth::exigerModule('utilisateurs');

        if ((int) $id === (int) (Auth::utilisateurConnecte()['id'] ?? 0)) {
            Flash::definir('Vous ne pouvez pas supprimer votre propre compte.');
            $this->redirigerVers('/utilisateurs');

            return;
        }

        if (Csrf::estValide($_POST['jeton_csrf'] ?? null)) {
            $resultat = (new UtilisateurDepot())->supprimer((int) $id);
            Flash::definir($resultat === true ? 'Utilisateur supprimé.' : (string) $resultat);
        }

        $this->redirigerVers('/utilisateurs');
    }

    /**
     * @param array<int, string> $erreurs
     * @param array<string, mixed> $valeursSoumises
     */
    private function afficherFormulaire(?Utilisateur $utilisateur, array $erreurs, array $valeursSoumises): void
    {
        $this->afficherVue('utilisateurs/formulaire', [
            'utilisateur'     => $utilisateur,
            'roles'           => Utilisateur::rolesDisponibles(),
            'erreurs'         => $erreurs,
            'valeursSoumises' => $valeursSoumises,
        ]);
    }

    private function traiterFormulaire(?Utilisateur $utilisateurExistant): void
    {
        if (!Csrf::estValide($_POST['jeton_csrf'] ?? null)) {
            $this->afficherFormulaire($utilisateurExistant, ['Formulaire expiré, merci de réessayer.'], $_POST);

            return;
        }

        $depot = new UtilisateurDepot();

        $nom = trim((string) ($_POST['nom'] ?? ''));
        $prenom = trim((string) ($_POST['prenom'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $role = (string) ($_POST['role'] ?? '');
        $actif = ($_POST['actif'] ?? '') === '1';
        $motDePasse = (string) ($_POST['mot_de_passe'] ?? '');

        $creation = $utilisateurExistant === null;
        $rolesValides = array_keys(Utilisateur::rolesDisponibles());

        $validation = (new Validation())
            ->requis($nom, 'Le nom est obligatoire.')
            ->requis($prenom, 'Le prénom est obligatoire.')
            ->requis($email, "L'email est obligatoire.")
            ->ajouterSi($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL), "Format d'email invalide.")
            ->dansListe($role, $rolesValides, 'Merci de choisir un rôle valide.');

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validation->ajouterSi(
                $depot->emailExiste($email, $utilisateurExistant?->id()),
                'Cet email est déjà utilisé par un autre compte.'
            );
        }

        if ($creation) {
            $validation
                ->requis($motDePasse, 'Le mot de passe est obligatoire pour un nouveau compte.')
                ->ajouterSi(
                    $motDePasse !== '' && mb_strlen($motDePasse) < self::LONGUEUR_MIN_MOT_DE_PASSE,
                    'Le mot de passe doit contenir au moins ' . self::LONGUEUR_MIN_MOT_DE_PASSE . ' caractères.'
                );
        } else {
            $validation->ajouterSi(
                $motDePasse !== '' && mb_strlen($motDePasse) < self::LONGUEUR_MIN_MOT_DE_PASSE,
                'Le nouveau mot de passe doit contenir au moins ' . self::LONGUEUR_MIN_MOT_DE_PASSE . ' caractères.'
            );
        }

        if (!$creation && $utilisateurExistant->id() === (int) (Auth::utilisateurConnecte()['id'] ?? 0)) {
            $validation->ajouterSi($role !== $utilisateurExistant->role(), 'Vous ne pouvez pas changer votre propre rôle.');
            $validation->ajouterSi(!$actif, 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        if (!$validation->estValide()) {
            $this->afficherFormulaire($utilisateurExistant, $validation->erreurs(), $_POST);

            return;
        }

        if ($creation) {
            $depot->creer($nom, $prenom, $email, $motDePasse, $role);
        } else {
            $depot->modifier($utilisateurExistant->id(), $nom, $prenom, $email, $role, $actif, $motDePasse !== '' ? $motDePasse : null);
        }

        Flash::definir($creation ? 'Utilisateur créé avec succès.' : 'Utilisateur modifié avec succès.');
        $this->redirigerVers('/utilisateurs');
    }
}
