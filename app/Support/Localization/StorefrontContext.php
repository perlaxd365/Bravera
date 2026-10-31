<?php

namespace App\Support\Localization;

use Illuminate\Http\Request;

final readonly class StorefrontContext
{
    public function __construct(
        public string $countryCode,
        public string $locale,
        public string $currency,
        public float $exchangeRate,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $supportedLocales = (array) config('storefront.supported_locales', ['es', 'en']);
        $country = self::detectCountry($request);
        $requestedLocale = strtolower((string) ($request->query('lang')
            ?: $request->cookie(config('storefront.cookies.locale'))));
        $locale = in_array($requestedLocale, $supportedLocales, true)
            ? $requestedLocale
            : self::detectLocale($request, $country, $supportedLocales);

        $requestedCurrency = strtoupper((string) ($request->query('currency')
            ?: $request->cookie(config('storefront.cookies.currency'))));
        $currency = in_array($requestedCurrency, ['PEN', 'USD'], true)
            ? $requestedCurrency
            : ($country === 'PE'
                ? (string) config('storefront.base_currency', 'PEN')
                : (string) config('storefront.international_currency', 'USD'));

        return new self(
            countryCode: $country,
            locale: $locale,
            currency: $currency,
            exchangeRate: max(0.01, (float) config('storefront.usd_rate', 3.49)),
        );
    }

    public static function current(): self
    {
        $context = request()->attributes->get('storefront');

        return $context instanceof self
            ? $context
            : self::fromRequest(request());
    }

    public function isInternational(): bool
    {
        return $this->currency === 'USD';
    }

    public function displayAmount(float $penAmount): float
    {
        return $this->isInternational()
            ? round($penAmount / $this->exchangeRate, 2)
            : round($penAmount, 2);
    }

    public function format(float $amount, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?: $this->currency);
        $value = number_format($amount, 2, '.', ',');

        return match ($currency) {
            'USD' => '$'.$value.' USD',
            default => 'S/ '.$value,
        };
    }

    public function formatFromPen(float $penAmount): string
    {
        return $this->format($this->displayAmount($penAmount));
    }

    public function chargedFromPen(float $penAmount): string
    {
        return $this->format($penAmount, 'PEN');
    }

    public function languageName(): string
    {
        return (string) data_get(
            config('storefront.language_names', []),
            $this->locale,
            strtoupper($this->locale),
        );
    }

    private static function detectCountry(Request $request): string
    {
        $country = '';

        foreach ((array) config('storefront.country_headers', []) as $header) {
            $value = strtoupper(trim((string) $request->header($header)));

            if (preg_match('/^[A-Z]{2}$/', $value)) {
                $country = $value;
                break;
            }
        }

        if ($country === '') {
            $country = (string) (app(IpCountryResolver::class)->resolve($request->ip()) ?? '');
        }

        if ($country === '') {
            $acceptLanguage = (string) $request->header('Accept-Language', '');

            if (preg_match('/(?:^|,)\s*[a-z]{2}(?:-[A-Z]{2})?/i', $acceptLanguage, $match)) {
                $parts = explode('-', str_replace('_', '-', $match[0]));
                $country = strtoupper((string) ($parts[1] ?? ''));
            }
        }

        if (! preg_match('/^[A-Z]{2}$/', $country) || in_array($country, ['XX', 'T1'], true)) {
            $country = str_starts_with(
                mb_strtolower(trim((string) $request->header('Accept-Language', ''))),
                'en',
            ) ? 'US' : strtoupper((string) config('storefront.default_country', 'PE'));
        }

        return $country;
    }

    /**
     * @param  list<string>  $supportedLocales
     */
    private static function detectLocale(Request $request, string $country, array $supportedLocales): string
    {
        if ($country === 'PE' && in_array('es', $supportedLocales, true)) {
            return 'es';
        }

        $acceptLanguage = mb_strtolower((string) $request->header('Accept-Language', ''));

        foreach (preg_split('/\s*,\s*/', $acceptLanguage) ?: [] as $language) {
            $locale = substr(trim(explode(';', $language)[0]), 0, 2);

            if (in_array($locale, $supportedLocales, true)) {
                return $locale;
            }
        }

        if ($country === 'US') {
            return 'en';
        }

        $fallback = (string) config('storefront.default_locale', 'es');

        return in_array($fallback, $supportedLocales, true) ? $fallback : 'es';
    }
}
