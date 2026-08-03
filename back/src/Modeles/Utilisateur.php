<?php

declare(strict_types=1);

namespace Wacdo\Modeles;

use InvalidArgumentException;

/**
 * Utilisateur du back-office (T04.1). Classe abstraite : chaque rôle métier
 * (Administration / Préparation / Accueil) est une sous-classe concrète qui
 * définit ses propres permissions (`modulesAutorises`) — polymorphisme plutôt
 * qu'un simple `if ($role === ...)` dispersé dans le code.
 */
abstract class Utilisateur
{
    /** @var array<string, class-string<Utilisateur>> */
    private const CLASSES_PAR_ROLE = [
        'administration' => Administrateur::class,
        'preparation'    => Preparateur::class,
        'accueil'        => AgentAccueil::class,
    ];

    public function __construct(
        private readonly int $id,
        private readonly string $nom,
        private readonly string $prenom,
        private readonly string $email,
        private readonly string $motDePasseHache,
        private readonly bool $actif,
    ) {
    }

    /**
     * Fabrique (Factory Method) : instancie la bonne sous-classe à partir
     * d'une ligne de la table `utilisateur`.
     *
     * @param array<string, mixed> $ligne
     */
    public static function depuisLigne(array $ligne): self
    {
        $classe = self::classePourRole((string) $ligne['role']);

        return new $classe(
            (int) $ligne['id'],
            (string) $ligne['nom'],
            (string) $ligne['prenom'],
            (string) $ligne['email'],
            (string) $ligne['mot_de_passe'],
            (bool) $ligne['actif'],
        );
    }

    /**
     * Instance "légère" (sans données personnelles) construite uniquement à
     * partir du rôle stocké en session — utilisée par le contrôle d'accès
     * (T04.5) pour interroger `peutAccederA()` sans repasser par la BDD.
     */
    public static function depuisRole(string $role): self
    {
        $classe = self::classePourRole($role);

        return new $classe(0, '', '', '', '', true);
    }

    private static function classePourRole(string $role): string
    {
        return self::CLASSES_PAR_ROLE[$role]
            ?? throw new InvalidArgumentException("Rôle utilisateur inconnu : {$role}");
    }

    /**
     * Rôles possibles et leur libellé — utilisé pour peupler le `<select>`
     * du formulaire de gestion des utilisateurs (T06.2).
     *
     * @return array<string, string>
     */
    public static function rolesDisponibles(): array
    {
        $roles = [];
        foreach (array_keys(self::CLASSES_PAR_ROLE) as $role) {
            $roles[$role] = self::depuisRole($role)->libelleRole();
        }

        return $roles;
    }

    public function verifierMotDePasse(string $motDePasseClair): bool
    {
        return password_verify($motDePasseClair, $this->motDePasseHache);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function nom(): string
    {
        return $this->nom;
    }

    public function prenom(): string
    {
        return $this->prenom;
    }

    public function nomComplet(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    public function email(): string
    {
        return $this->email;
    }

    public function estActif(): bool
    {
        return $this->actif;
    }

    public function peutAccederA(string $module): bool
    {
        return in_array($module, $this->modulesAutorises(), true);
    }

    /** Identifiant technique du rôle (colonne `role` en base). */
    abstract public function role(): string;

    /** Libellé affichable du rôle. */
    abstract public function libelleRole(): string;

    /** Liste des modules du back-office accessibles à ce rôle. */
    abstract public function modulesAutorises(): array;

    /**
     * Données stockées en session — jamais le mot de passe, même haché.
     *
     * @return array{id: int, nom: string, prenom: string, email: string, role: string, libelleRole: string}
     */
    public function versSession(): array
    {
        return [
            'id'          => $this->id,
            'nom'         => $this->nom,
            'prenom'      => $this->prenom,
            'email'       => $this->email,
            'role'        => $this->role(),
            'libelleRole' => $this->libelleRole(),
        ];
    }
}
