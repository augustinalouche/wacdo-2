<?php

declare(strict_types=1);

namespace Wacdo\Core;

/**
 * Message de confirmation à usage unique, affiché après une redirection
 * (pattern POST/redirect/GET) — ex. "Produit créé avec succès".
 */
final class Flash
{
    private const CLE_SESSION = 'message_flash';

    public static function definir(string $message): void
    {
        $_SESSION[self::CLE_SESSION] = $message;
    }

    public static function consommer(): ?string
    {
        $message = $_SESSION[self::CLE_SESSION] ?? null;
        unset($_SESSION[self::CLE_SESSION]);

        return $message;
    }
}
