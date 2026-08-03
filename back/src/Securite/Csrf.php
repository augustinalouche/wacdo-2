<?php

declare(strict_types=1);

namespace Wacdo\Securite;

/**
 * Protection CSRF sur les formulaires sensibles (T04.6) : un jeton aléatoire
 * est stocké en session et doit être renvoyé (champ caché) à chaque
 * soumission de formulaire POST.
 */
final class Csrf
{
    private const CLE_SESSION = 'jeton_csrf';

    public static function jeton(): string
    {
        if (empty($_SESSION[self::CLE_SESSION])) {
            $_SESSION[self::CLE_SESSION] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CLE_SESSION];
    }

    /** Champ caché prêt à insérer dans un `<form>`. */
    public static function champCache(): string
    {
        return '<input type="hidden" name="jeton_csrf" value="' . htmlspecialchars(self::jeton(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function estValide(?string $jetonRecu): bool
    {
        return is_string($jetonRecu)
            && !empty($_SESSION[self::CLE_SESSION])
            && hash_equals((string) $_SESSION[self::CLE_SESSION], $jetonRecu);
    }
}
