<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GoogleAnalyticsService
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const REPORT_URL = 'https://analyticsdata.googleapis.com/v1beta/properties/%s:runReport';

    private const SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';

    public function isConfigured(): bool
    {
        $propertyId = preg_replace('/\D+/', '', (string) config('services.google_analytics.property_id'));
        $credentialsPath = $this->credentialsPath();

        return $propertyId !== ''
            && $credentialsPath !== ''
            && is_file($credentialsPath)
            && is_readable($credentialsPath);
    }

    /**
     * @return array{visitorsToday: int, visitorsYesterday: int, pageViewsToday: int}
     */
    public function traffic(): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Google Analytics 4 no está configurado.');
        }

        $propertyId = preg_replace('/\D+/', '', (string) config('services.google_analytics.property_id'));

        return Cache::remember(
            'ga4-traffic:'.$propertyId,
            now()->addMinute(),
            fn (): array => $this->requestTraffic($propertyId),
        );
    }

    /**
     * @return array{visitorsToday: int, visitorsYesterday: int, pageViewsToday: int}
     */
    private function requestTraffic(string $propertyId): array
    {
        $credentials = $this->credentials();
        $accessToken = $this->accessToken($credentials);

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->timeout(10)
            ->post(sprintf(self::REPORT_URL, $propertyId), [
                'dateRanges' => [[
                    'startDate' => 'yesterday',
                    'endDate' => 'today',
                ]],
                'dimensions' => [[
                    'name' => 'date',
                ]],
                'metrics' => [
                    ['name' => 'activeUsers'],
                    ['name' => 'screenPageViews'],
                ],
                'limit' => 2,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Google Analytics rechazó la consulta con estado '.$response->status().'.',
            );
        }

        $today = now()->format('Ymd');
        $yesterday = now()->subDay()->format('Ymd');
        $traffic = [
            'visitorsToday' => 0,
            'visitorsYesterday' => 0,
            'pageViewsToday' => 0,
        ];

        foreach ($response->json('rows', []) as $row) {
            $date = (string) data_get($row, 'dimensionValues.0.value');
            $visitors = (int) data_get($row, 'metricValues.0.value', 0);
            $pageViews = (int) data_get($row, 'metricValues.1.value', 0);

            if ($date === $today) {
                $traffic['visitorsToday'] = $visitors;
                $traffic['pageViewsToday'] = $pageViews;
            }

            if ($date === $yesterday) {
                $traffic['visitorsYesterday'] = $visitors;
            }
        }

        return $traffic;
    }

    /**
     * @param  array{client_email: string, private_key: string}  $credentials
     */
    protected function accessToken(array $credentials): string
    {
        $cacheKey = 'ga4-token:'.hash('sha256', $credentials['client_email']);

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(50),
            fn (): string => $this->requestAccessToken($credentials),
        );
    }

    /**
     * @param  array{client_email: string, private_key: string}  $credentials
     */
    private function requestAccessToken(array $credentials): string
    {
        $now = time();
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));
        $unsignedToken = $header.'.'.$claims;

        $privateKey = openssl_pkey_get_private($credentials['private_key']);

        if ($privateKey === false) {
            throw new RuntimeException('La llave privada de Google Analytics no es válida.');
        }

        $signature = '';

        if (! openssl_sign($unsignedToken, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('No se pudo firmar la solicitud de Google Analytics.');
        }

        $response = Http::asForm()
            ->acceptJson()
            ->timeout(10)
            ->post(self::TOKEN_URL, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsignedToken.'.'.$this->base64UrlEncode($signature),
            ]);

        if ($response->failed() || blank($response->json('access_token'))) {
            throw new RuntimeException(
                'Google rechazó las credenciales de Analytics con estado '.$response->status().'.',
            );
        }

        return (string) $response->json('access_token');
    }

    /**
     * @return array{client_email: string, private_key: string}
     */
    protected function credentials(): array
    {
        $path = $this->credentialsPath();

        try {
            $credentials = json_decode(
                (string) file_get_contents($path),
                true,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (Throwable $exception) {
            throw new RuntimeException('No se pudo leer la credencial de Google Analytics.', previous: $exception);
        }

        if (
            ! is_array($credentials)
            || blank($credentials['client_email'] ?? null)
            || blank($credentials['private_key'] ?? null)
        ) {
            throw new RuntimeException('La credencial de Google Analytics está incompleta.');
        }

        return [
            'client_email' => (string) $credentials['client_email'],
            'private_key' => (string) $credentials['private_key'],
        ];
    }

    private function credentialsPath(): string
    {
        $path = trim((string) config('services.google_analytics.credentials_path'));

        return $path !== ''
            ? $path
            : storage_path('app/analytics/service-account.json');
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
