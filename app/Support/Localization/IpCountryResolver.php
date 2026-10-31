<?php

namespace App\Support\Localization;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class IpCountryResolver
{
    public function resolve(?string $ip): ?string
    {
        if (! $this->isPublicIp($ip) || ! config('storefront.geoip.enabled', true)) {
            return null;
        }

        $cacheHours = max(1, (int) config('storefront.geoip.cache_hours', 24));
        $cacheKey = 'storefront:country:'.hash('sha256', (string) $ip);

        try {
            return Cache::remember($cacheKey, now()->addHours($cacheHours), function () use ($ip): ?string {
                $template = (string) config(
                    'storefront.geoip.endpoint',
                    'https://ipwho.is/{ip}',
                );
                $url = str_replace('{ip}', rawurlencode((string) $ip), $template);

                $response = Http::acceptJson()
                    ->timeout(max(1, (int) config('storefront.geoip.timeout', 2)))
                    ->get($url);

                if (! $response->successful() || data_get($response->json(), 'success') === false) {
                    return null;
                }

                $country = strtoupper((string) data_get($response->json(), 'country_code', ''));

                return preg_match('/^[A-Z]{2}$/', $country) && ! in_array($country, ['XX', 'T1'], true)
                    ? $country
                    : null;
            });
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function isPublicIp(?string $ip): bool
    {
        return is_string($ip)
            && filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            ) !== false;
    }
}
