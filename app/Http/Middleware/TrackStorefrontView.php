<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackStorefrontView
{
    /**
     * @var array<int, string>
     */
    private const TRACKED_ROUTES = [
        'home',
        'store.category',
        'store.product',
        'store.offers',
        'about',
        'terms',
        'returns',
        'privacy',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldTrack($request)) {
            return $next($request);
        }

        $visitorToken = (string) $request->cookie('brevare_visitor');
        $newVisitorToken = ! Str::isUuid($visitorToken);

        if ($newVisitorToken) {
            $visitorToken = (string) Str::uuid();
        }

        $response = $next($request);

        if (! $response->isSuccessful()) {
            return $response;
        }

        try {
            PageView::create([
                'product_id' => $this->productId($request),
                'visitor_token' => $visitorToken,
                'path' => '/'.ltrim($request->path(), '/'),
                'viewed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            // La analítica no debe impedir que la tienda cargue.
            report($exception);

            return $response;
        }

        if ($newVisitorToken) {
            $response->headers->setCookie(Cookie::make(
                'brevare_visitor',
                $visitorToken,
                60 * 24 * 365,
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'lax',
            ));
        }

        return $response;
    }

    private function shouldTrack(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->cookie(config('meta.consent_cookie', 'brevare_consent')) !== 'all') {
            return false;
        }

        if ($request->headers->get('DNT') === '1') {
            return false;
        }

        if (in_array($request->route()?->getName(), self::TRACKED_ROUTES, true) === false) {
            return false;
        }

        $userAgent = strtolower((string) $request->userAgent());

        foreach (['bot', 'crawler', 'spider', 'slurp', 'lighthouse', 'headlesschrome'] as $signature) {
            if (str_contains($userAgent, $signature)) {
                return false;
            }
        }

        return true;
    }

    private function productId(Request $request): ?int
    {
        if ($request->route()?->getName() !== 'store.product') {
            return null;
        }

        $slug = $request->route('slug');

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        $productId = Product::query()->where('slug', $slug)->value('id');

        return $productId === null ? null : (int) $productId;
    }
}
