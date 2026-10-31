<?php

namespace App\Http\Controllers\Store;

use App\Support\Localization\StorefrontContext;
use Illuminate\Http\RedirectResponse;

class StorefrontPreferenceController
{
    public function language(string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, (array) config('storefront.supported_locales', []), true), 404);

        return redirect()->back()->withCookie(cookie()->forever(
            (string) config('storefront.cookies.locale'),
            $locale,
            '/',
            null,
            request()->isSecure(),
            true,
            false,
            'lax',
        ));
    }

    public function currency(string $currency): RedirectResponse
    {
        $currency = strtoupper($currency);
        abort_unless(in_array($currency, ['PEN', 'USD'], true), 404);

        return redirect()->back()->withCookie(cookie()->forever(
            (string) config('storefront.cookies.currency'),
            $currency,
            '/',
            null,
            request()->isSecure(),
            true,
            false,
            'lax',
        ));
    }
}
