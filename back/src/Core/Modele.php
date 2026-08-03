<?php

declare(strict_types=1);

namespace Wacdo\Core;

use PDO;

/**
 * Classe de base pour tous les modeles (T03.6).
 */
abstract class Modele
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connexion();
    }
}
