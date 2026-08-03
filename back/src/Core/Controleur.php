<?php

declare(strict_types=1);

namespace Wacdo\Core;

use RuntimeException;

/**
 * Classe de base pour tous les controleurs (T03.6).
 */
abstract class Controleur
{
    /**
     * Affiche une vue PHP en lui passant des donnees (extraites en variables locales).
     *
     * @param array<string, mixed> $donnees
     */
    protected function afficherVue(string $vue, array $donnees = []): void
    {
        $cheminVue = dirname(__DIR__) . '/Vues/' . $vue . '.php';

        if (!is_file($cheminVue)) {
            throw new RuntimeException("Vue introuvable : {$vue}");
        }

        extract($donnees, EXTR_SKIP);
        require $cheminVue;
    }

    /**
     * Repond en JSON — utilise par les controleurs d'API (EPIC 8).
     */
    protected function repondreJson(mixed $donnees, int $codeHttp = 200): void
    {
        http_response_code($codeHttp);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function rediriger(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
