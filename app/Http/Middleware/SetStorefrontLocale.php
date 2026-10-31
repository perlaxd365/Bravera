<?php

namespace App\Http\Middleware;

use App\Support\Localization\StorefrontContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetStorefrontLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = StorefrontContext::fromRequest($request);
        $request->attributes->set('storefront', $context);
        app()->setLocale($context->locale);
        view()->share('storefront', $context);

        return $next($request);
    }
}
