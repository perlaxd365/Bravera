<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceCanonicalHost
{
    /**
     * Keep the storefront on one crawlable origin.
     *
     * @var array<int, string>
     */
    private const STORE_HOSTS = ['brevare.com', 'www.brevare.com'];

    public function handle(Request $request, Closure $next): Response
    {
        $canonicalUrl = rtrim(
            (string) config('seo.canonical_url', 'https://brevare.com'),
            '/',
        );
        $canonical = parse_url($canonicalUrl);
        $requestHost = strtolower($request->getHost());

        if (
            ! is_array($canonical)
            || empty($canonical['host'])
            || ! in_array($requestHost, self::STORE_HOSTS, true)
        ) {
            return $next($request);
        }

        $canonicalHost = strtolower($canonical['host']);
        $canonicalScheme = strtolower($canonical['scheme'] ?? 'https');
        $requestScheme = strtolower($request->getScheme());

        if ($requestHost === $canonicalHost && $requestScheme === $canonicalScheme) {
            return $next($request);
        }

        return redirect()->away(
            $canonicalScheme.'://'.$canonicalHost.$request->getRequestUri(),
            301,
        );
    }
}
