<?php

namespace App\Support\Auth;

use Illuminate\Http\Request;

final class LoginRedirect
{
    /**
     * Guarda el retorno solicitado desde /login?redirect=... para que también
     * sobreviva al viaje de ida y vuelta con Google.
     */
    public static function remember(Request $request): void
    {
        $redirect = $request->query('redirect');

        if (is_string($redirect) && trim($redirect) !== '') {
            session()->put(
                'url.intended',
                self::sanitize($redirect, route('home'))
            );
        }
    }

    /**
     * Devuelve y consume el destino pendiente, evitando pantallas internas de
     * Laravel, endpoints POST y URLs externas.
     */
    public static function pull(Request $request, string $default): string
    {
        $intended = session()->pull('url.intended');

        return self::sanitize(is_string($intended) ? $intended : null, $default);
    }

    public static function sanitize(?string $url, string $default): string
    {
        $url = trim((string) $url);

        if ($url === '' || str_starts_with($url, '//')) {
            return $default;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '' || $path === '/') {
            return $default;
        }

        if ($path === '/dashboard' || str_starts_with($path, '/dashboard/')) {
            return $default;
        }

        // Los endpoints internos de Livewire solo aceptan POST y nunca deben
        // usarse como destino de una navegación GET después de autenticar.
        if ($path === '/livewire' || str_starts_with($path, '/livewire/')) {
            return $default;
        }

        if (in_array($path, ['/login', '/register', '/logout'], true)
            || str_starts_with($path, '/auth/')
            || str_starts_with($path, '/password/')) {
            return $default;
        }

        if (preg_match('#/favoritos/[^/]+/toggle$#', $path)) {
            return $default;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (is_string($host) && $host !== ''
            && strcasecmp($host, request()->getHost()) !== 0) {
            return $default;
        }

        return $url;
    }
}
