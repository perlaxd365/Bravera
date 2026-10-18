<?php

namespace App\Http\Controllers;

use App\Models\ShalomAgency;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ShalomAgencyController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $agencies = Cache::remember('shalom:agencies:active:v2', now()->addMinutes(10), function () {
            return ShalomAgency::query()
                ->active()
                ->orderBy('department')
                ->orderBy('province')
                ->orderBy('district')
                ->orderBy('address')
                ->get([
                    'id',
                    'code',
                    'department',
                    'province',
                    'district',
                    'address',
                    'modality',
                    'latitude',
                    'longitude',
                    'geocode_precision',
                ])
                ->map(fn (ShalomAgency $agency) => [
                    'id' => $agency->id,
                    'code' => $agency->code,
                    'department' => $agency->department,
                    'province' => $agency->province,
                    'district' => $agency->district,
                    'address' => $agency->address,
                    'modality' => $agency->modality,
                    'lat' => $agency->latitude,
                    'lng' => $agency->longitude,
                    'precision' => $agency->geocode_precision,
                ])
                ->values()
                ->all();
        });

        return response()
            ->json(['agencies' => $agencies])
            ->header('Cache-Control', 'public, max-age=600');
    }
}
