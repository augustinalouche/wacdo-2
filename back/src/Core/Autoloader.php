<?php

declare(strict_types=1);

namespace Wacdo\Core;

/**
 * Autoload maison (T03.3) : associe un prefixe d'espace de noms a un dossier
 * de base, sans dependre de Composer.
 */
final class Autoloader
{
    /** @var array<string, string> */
    private static array $prefixes = [];

    public static function register(): void
    {
        spl_autoload_register([self::class, 'charger']);
    }

    public static function ajouterEspaceDeNoms(string $prefixe, string $dossierBase): void
    {
        $prefixe = trim($prefixe, '\\') . '\\';
        $dossierBase = rtrim($dossierBase, '/\\') . DIRECTORY_SEPARATOR;
        self::$prefixes[$prefixe] = $dossierBase;
    }

    private static function charger(string $classe): void
    {
        foreach (self::$prefixes as $prefixe => $dossierBase) {
            if (strncmp($prefixe, $classe, strlen($prefixe)) !== 0) {
                continue;
            }

            $chemin = $dossierBase
                . str_replace('\\', DIRECTORY_SEPARATOR, substr($classe, strlen($prefixe)))
                . '.php';

            if (is_file($chemin)) {
                require $chemin;

                return;
            }
        }
    }
}
