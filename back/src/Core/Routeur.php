<?php

declare(strict_types=1);

namespace Wacdo\Core;

/**
 * Routeur / front controller (T03.5) : associe une methode HTTP + un chemin
 * (avec parametres "{id}") a une action (callable ou [Controleur::class, 'methode']).
 */
final class Routeur
{
    /** @var array<int, array{methode: string, regex: string, action: callable|array}> */
    private array $routes = [];

    public function get(string $chemin, callable|array $action): void
    {
        $this->ajouter('GET', $chemin, $action);
    }

    public function post(string $chemin, callable|array $action): void
    {
        $this->ajouter('POST', $chemin, $action);
    }

    public function ajouter(string $methode, string $chemin, callable|array $action): void
    {
        $this->routes[] = [
            'methode' => strtoupper($methode),
            'regex'   => $this->convertirEnRegex($chemin),
            'action'  => $action,
        ];
    }

    public function distribuer(string $methode, string $uriBrute): void
    {
        $uri = parse_url($uriBrute, PHP_URL_PATH) ?: '/';
        $uri = $this->retirerCheminDeBase($uri);

        foreach ($this->routes as $route) {
            if ($route['methode'] !== strtoupper($methode)) {
                continue;
            }

            if (preg_match($route['regex'], $uri, $correspondances) === 1) {
                $parametres = array_filter(
                    $correspondances,
                    static fn ($cle) => is_string($cle),
                    ARRAY_FILTER_USE_KEY
                );
                $this->executer($route['action'], $parametres);

                return;
            }
        }

        $this->page404();
    }

    private function convertirEnRegex(string $chemin): string
    {
        $motif = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', rtrim($chemin, '/'));

        return '#^' . $motif . '/?$#';
    }

    /**
     * @param array<string, mixed> $parametres
     */
    private function executer(callable|array $action, array $parametres): void
    {
        if (is_array($action)) {
            [$classe, $methode] = $action;
            $controleur = new $classe();
            $controleur->$methode(...array_values($parametres));

            return;
        }

        $action(...array_values($parametres));
    }

    /**
     * Retire le prefixe correspondant au dossier de deploiement
     * (ex. "/wacdo2/back") pour ne comparer que le chemin applicatif.
     */
    private function retirerCheminDeBase(string $uri): string
    {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        return $uri === '' ? '/' : $uri;
    }

    private function page404(): void
    {
        http_response_code(404);
        require dirname(__DIR__) . '/Vues/erreurs/404.php';
    }
}
