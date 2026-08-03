<?php

declare(strict_types=1);

namespace Wacdo\Modeles;

use RuntimeException;
use Wacdo\Core\Modele;

/**
 * Dépôt (repository) pour les tables `commande` / `ligne_commande` /
 * `composition_menu` (T07.1). Seule classe à parler SQL pour les commandes.
 *
 * Le prix n'est jamais fourni par l'appelant : `creer()` recalcule et fige
 * `prix_unitaire` à partir du catalogue courant (même règle de sécurité que
 * la future API — cf. `docs/conception/04-structure-json.md`, `T09.x`).
 */
final class CommandeDepot extends Modele
{
    /**
     * Historique filtrable (T07.7) : le plus récent en premier par défaut.
     *
     * @return array<int, Commande>
     */
    public function trouverToutes(?string $statutLibelle = null, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $conditions = [];
        $parametres = [];

        if ($statutLibelle !== null) {
            $conditions[] = 's.libelle = :statut';
            $parametres['statut'] = $statutLibelle;
        }
        if ($dateDebut !== null) {
            $conditions[] = 'DATE(c.date_heure) >= :date_debut';
            $parametres['date_debut'] = $dateDebut;
        }
        if ($dateFin !== null) {
            $conditions[] = 'DATE(c.date_heure) <= :date_fin';
            $parametres['date_fin'] = $dateFin;
        }

        $ou = $conditions === [] ? '' : ('WHERE ' . implode(' AND ', $conditions));

        $requete = $this->pdo->prepare(
            "SELECT c.id, c.numero_affichage, c.date_heure, s.libelle AS statut_libelle,
                    c.montant_total, c.origine, c.utilisateur_id
             FROM commande c
             JOIN statut s ON s.id = c.statut_id
             {$ou}
             ORDER BY c.date_heure DESC"
        );
        $requete->execute($parametres);

        return array_map(fn (array $l) => $this->hydraterAvecLignes($l), $requete->fetchAll());
    }

    /**
     * File d'attente par statut (T07.3 : à préparer / T07.5 : prêtes à
     * remettre), triée par heure croissante — premier arrivé, premier servi.
     *
     * @param array<int, string> $libelles
     * @return array<int, Commande>
     */
    public function trouverParStatuts(array $libelles): array
    {
        $marqueurs = implode(',', array_fill(0, count($libelles), '?'));
        $requete = $this->pdo->prepare(
            "SELECT c.id, c.numero_affichage, c.date_heure, s.libelle AS statut_libelle,
                    c.montant_total, c.origine, c.utilisateur_id
             FROM commande c
             JOIN statut s ON s.id = c.statut_id
             WHERE s.libelle IN ({$marqueurs})
             ORDER BY c.date_heure ASC"
        );
        $requete->execute($libelles);

        return array_map(fn (array $l) => $this->hydraterAvecLignes($l), $requete->fetchAll());
    }

    public function changerStatut(int $commandeId, string $nouveauLibelle): void
    {
        $statutId = $this->statutIdParLibelle($nouveauLibelle);
        $this->pdo->prepare('UPDATE commande SET statut_id = :statut_id WHERE id = :id')
            ->execute(['statut_id' => $statutId, 'id' => $commandeId]);
    }

    /**
     * Crée une commande complète (en-tête + lignes + compositions) dans une
     * transaction : soit tout est inséré, soit rien ne l'est.
     *
     * @param array<int, array{type:string, produitId?:int, menuId?:int, tailleId?:int|null, quantite:int, composition?:array{accompagnementProduitId:int, accompagnementTailleId:int, boissonProduitId:int, boissonTailleId:int, sauceId:int}}> $lignes
     *
     * @throws RuntimeException si une ligne référence un produit/menu invalide, indisponible ou une taille inconnue
     */
    public function creer(string $numeroAffichage, string $origine, array $lignes, ?int $utilisateurId): int
    {
        $lignesResolues = $this->resoudrePrixDesLignes($lignes);
        $montantTotal = array_reduce(
            $lignesResolues,
            static fn (float $total, array $l) => $total + round($l['prixUnitaire'] * $l['quantite'], 2),
            0.0
        );

        $this->pdo->beginTransaction();

        try {
            $statutId = $this->statutIdParLibelle('En attente');

            $requeteCommande = $this->pdo->prepare(
                'INSERT INTO commande (numero_affichage, statut_id, montant_total, origine, utilisateur_id)
                 VALUES (:numero, :statut_id, :montant, :origine, :utilisateur_id)'
            );
            $requeteCommande->execute([
                'numero'        => $numeroAffichage,
                'statut_id'     => $statutId,
                'montant'       => round($montantTotal, 2),
                'origine'       => $origine,
                'utilisateur_id' => $utilisateurId,
            ]);
            $commandeId = (int) $this->pdo->lastInsertId();

            $requeteLigne = $this->pdo->prepare(
                'INSERT INTO ligne_commande (commande_id, produit_id, menu_id, taille_id, quantite, prix_unitaire)
                 VALUES (:commande_id, :produit_id, :menu_id, :taille_id, :quantite, :prix_unitaire)'
            );
            $requeteComposition = $this->pdo->prepare(
                'INSERT INTO composition_menu
                    (ligne_commande_id, accompagnement_produit_id, accompagnement_taille_id, boisson_produit_id, boisson_taille_id, sauce_id)
                 VALUES (:ligne_id, :accompagnement_produit_id, :accompagnement_taille_id, :boisson_produit_id, :boisson_taille_id, :sauce_id)'
            );

            foreach ($lignesResolues as $ligne) {
                $requeteLigne->execute([
                    'commande_id'   => $commandeId,
                    'produit_id'    => $ligne['produitId'],
                    'menu_id'       => $ligne['menuId'],
                    'taille_id'     => $ligne['tailleId'],
                    'quantite'      => $ligne['quantite'],
                    'prix_unitaire' => $ligne['prixUnitaire'],
                ]);

                if ($ligne['composition'] !== null) {
                    $requeteComposition->execute([
                        'ligne_id'                  => (int) $this->pdo->lastInsertId(),
                        'accompagnement_produit_id' => $ligne['composition']['accompagnementProduitId'],
                        'accompagnement_taille_id'  => $ligne['composition']['accompagnementTailleId'],
                        'boisson_produit_id'        => $ligne['composition']['boissonProduitId'],
                        'boisson_taille_id'         => $ligne['composition']['boissonTailleId'],
                        'sauce_id'                  => $ligne['composition']['sauceId'],
                    ]);
                }
            }

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();

            throw $exception;
        }

        return $commandeId;
    }

    /**
     * Recalcule le prix unitaire de chaque ligne à partir du catalogue
     * courant — ne fait jamais confiance à un prix transmis par le formulaire.
     *
     * @param array<int, array<string, mixed>> $lignes
     * @return array<int, array{produitId:?int, menuId:?int, tailleId:?int, quantite:int, prixUnitaire:float, composition:?array}>
     */
    private function resoudrePrixDesLignes(array $lignes): array
    {
        $produitDepot = new ProduitDepot();
        $menuDepot = new MenuDepot();
        $supplementsParTailleId = array_column((new ReferentielDepot())->tailles(), 'supplement', 'id');

        $resolues = [];

        foreach ($lignes as $ligne) {
            if (($ligne['type'] ?? null) === 'produit') {
                $produit = $produitDepot->trouverParId((int) $ligne['produitId']);
                if ($produit === null || !$produit->disponible) {
                    throw new RuntimeException("Produit indisponible ou introuvable (id {$ligne['produitId']}).");
                }

                $tailleId = $ligne['tailleId'] ?? null;
                if ($tailleId !== null) {
                    $tailleTrouvee = null;
                    foreach ($produit->tailles as $taille) {
                        if ($taille['tailleId'] === (int) $tailleId) {
                            $tailleTrouvee = $taille;
                            break;
                        }
                    }
                    if ($tailleTrouvee === null) {
                        throw new RuntimeException("Taille invalide pour le produit « {$produit->nom} ».");
                    }
                    $prixUnitaire = $tailleTrouvee['prix'];
                } else {
                    if ($produit->prix === null) {
                        throw new RuntimeException("Le produit « {$produit->nom} » nécessite le choix d'une taille.");
                    }
                    $prixUnitaire = $produit->prix;
                }

                $resolues[] = [
                    'produitId'    => $produit->id,
                    'menuId'       => null,
                    'tailleId'     => $tailleId !== null ? (int) $tailleId : null,
                    'quantite'     => (int) $ligne['quantite'],
                    'prixUnitaire' => $prixUnitaire,
                    'composition'  => null,
                ];

                continue;
            }

            if (($ligne['type'] ?? null) === 'menu') {
                $menu = $menuDepot->trouverParId((int) $ligne['menuId']);
                if ($menu === null || !$menu->disponible) {
                    throw new RuntimeException("Menu indisponible ou introuvable (id {$ligne['menuId']}).");
                }

                $composition = $ligne['composition'];
                $supplementAccompagnement = $supplementsParTailleId[$composition['accompagnementTailleId']] ?? null;
                $supplementBoisson = $supplementsParTailleId[$composition['boissonTailleId']] ?? null;
                if ($supplementAccompagnement === null || $supplementBoisson === null) {
                    throw new RuntimeException("Taille de composition invalide pour le menu « {$menu->nom} ».");
                }

                $resolues[] = [
                    'produitId'    => null,
                    'menuId'       => $menu->id,
                    'tailleId'     => null,
                    'quantite'     => (int) $ligne['quantite'],
                    'prixUnitaire' => round($menu->prixBase + $supplementAccompagnement + $supplementBoisson, 2),
                    'composition'  => $composition,
                ];

                continue;
            }

            throw new RuntimeException('Type de ligne de commande inconnu.');
        }

        return $resolues;
    }

    private function statutIdParLibelle(string $libelle): int
    {
        $requete = $this->pdo->prepare('SELECT id FROM statut WHERE libelle = :libelle');
        $requete->execute(['libelle' => $libelle]);
        $id = $requete->fetchColumn();

        if ($id === false) {
            throw new RuntimeException("Statut de commande inconnu : {$libelle}");
        }

        return (int) $id;
    }

    /**
     * @param array<string, mixed> $enTete
     */
    private function hydraterAvecLignes(array $enTete): Commande
    {
        $commandeId = (int) $enTete['id'];

        $requete = $this->pdo->prepare(
            "SELECT lc.id, lc.produit_id, p.nom AS produit_nom, lc.menu_id, m.nom AS menu_nom,
                    lc.taille_id, t.libelle AS taille_libelle, lc.quantite, lc.prix_unitaire,
                    cm.accompagnement_produit_id, pa.nom AS accompagnement_nom, ta.libelle AS accompagnement_taille_libelle,
                    cm.boisson_produit_id, pb.nom AS boisson_nom, tb.libelle AS boisson_taille_libelle,
                    cm.sauce_id, s.nom AS sauce_nom
             FROM ligne_commande lc
             LEFT JOIN produit p ON p.id = lc.produit_id
             LEFT JOIN menu m ON m.id = lc.menu_id
             LEFT JOIN taille t ON t.id = lc.taille_id
             LEFT JOIN composition_menu cm ON cm.ligne_commande_id = lc.id
             LEFT JOIN produit pa ON pa.id = cm.accompagnement_produit_id
             LEFT JOIN taille ta ON ta.id = cm.accompagnement_taille_id
             LEFT JOIN produit pb ON pb.id = cm.boisson_produit_id
             LEFT JOIN taille tb ON tb.id = cm.boisson_taille_id
             LEFT JOIN sauce s ON s.id = cm.sauce_id
             WHERE lc.commande_id = :commande_id
             ORDER BY lc.id"
        );
        $requete->execute(['commande_id' => $commandeId]);

        $lignes = array_map(static function (array $l): LigneCommande {
            $composition = null;
            if ($l['accompagnement_produit_id'] !== null) {
                $composition = [
                    'accompagnement' => "{$l['accompagnement_nom']} ({$l['accompagnement_taille_libelle']})",
                    'boisson'        => "{$l['boisson_nom']} ({$l['boisson_taille_libelle']})",
                    'sauce'          => (string) $l['sauce_nom'],
                ];
            }

            return new LigneCommande(
                (int) $l['id'],
                $l['produit_id'] !== null ? (int) $l['produit_id'] : null,
                $l['produit_nom'] !== null ? (string) $l['produit_nom'] : null,
                $l['menu_id'] !== null ? (int) $l['menu_id'] : null,
                $l['menu_nom'] !== null ? (string) $l['menu_nom'] : null,
                $l['taille_id'] !== null ? (int) $l['taille_id'] : null,
                $l['taille_libelle'] !== null ? (string) $l['taille_libelle'] : null,
                (int) $l['quantite'],
                (float) $l['prix_unitaire'],
                $composition,
            );
        }, $requete->fetchAll());

        return new Commande(
            $commandeId,
            (string) $enTete['numero_affichage'],
            (string) $enTete['date_heure'],
            (string) $enTete['statut_libelle'],
            (float) $enTete['montant_total'],
            (string) $enTete['origine'],
            $enTete['utilisateur_id'] !== null ? (int) $enTete['utilisateur_id'] : null,
            $lignes,
        );
    }
}
