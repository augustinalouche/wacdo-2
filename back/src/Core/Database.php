<?php

declare(strict_types=1);

namespace Wacdo\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Connexion PDO unique partagee par toute l'application (T03.4).
 * Toutes les requetes de l'application doivent passer par des requetes
 * preparees (voir T09.1) — cette classe ne fait qu'exposer l'instance PDO.
 */
final class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function connexion(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/config.php';
            $db = $config['db'];

            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s;charset=%s',
                $db['driver'],
                $db['host'],
                $db['port'],
                $db['database'],
                $db['charset']
            );

            try {
                self::$instance = new PDO($dsn, $db['user'], $db['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $exception) {
                throw new RuntimeException('Connexion a la base de donnees impossible.', 0, $exception);
            }
        }

        return self::$instance;
    }
}
