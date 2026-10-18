<?php

namespace App\Modules\Shipping\Services;

use App\Models\ShalomAgency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NominatimGeocoder
{
    /**
     * Geocodifica una dirección una sola vez y cachea el resultado.
     *
     * @return array{lat: float, lon: float, display_name: ?string}|null
     */
    public function geocode(string $query): ?array
    {
        $query = trim($query);

        if ($query === '') {
            return null;
        }

        return Cache::remember(
            'nominatim:'.md5(mb_strtolower($query)),
            now()->addDays(30),
            function () use ($query) {
                $response = Http::withHeaders([
                    'User-Agent' => (string) config('services.nominatim.user_agent'),
                    'Accept-Language' => 'es',
                ])
                    ->timeout(20)
                    ->get(rtrim((string) config('services.nominatim.url'), '/').'/search', [
                        'format' => 'json',
                        'limit' => 1,
                        'countrycodes' => 'pe',
                        'q' => $query,
                    ]);

                if (! $response->ok()) {
                    return null;
                }

                $row = $response->json('0');

                if (! is_array($row) || ! isset($row['lat'], $row['lon'])) {
                    return null;
                }

                return [
                    'lat' => (float) $row['lat'],
                    'lon' => (float) $row['lon'],
                    'display_name' => $row['display_name'] ?? null,
                ];
            }
        );
    }

    public function geocodeAgency(ShalomAgency $agency): bool
    {
        $address = trim($agency->address);
        $districtOnly = mb_strtoupper($address) === mb_strtoupper(trim($agency->district));

        $districtQuery = trim(implode(', ', array_filter([
            $agency->district,
            $agency->province,
            $agency->department,
            'Peru',
        ])));

        $result = $districtOnly
            ? $this->geocode($districtQuery)
            : $this->geocode(trim(implode(', ', array_filter([
                $address,
                $agency->district,
                $agency->province,
                $agency->department,
                'Peru',
            ]))));

        $precision = $districtOnly ? 'district' : 'exact';

        if ($result === null && ! $districtOnly) {
            $result = $this->geocode($districtQuery);
            $precision = 'district';
        }

        if ($result === null) {
            return false;
        }

        $agency->update([
            'latitude' => $result['lat'],
            'longitude' => $result['lon'],
            'geocode_precision' => $precision,
            'geocoded_at' => now(),
        ]);

        return true;
    }
}
