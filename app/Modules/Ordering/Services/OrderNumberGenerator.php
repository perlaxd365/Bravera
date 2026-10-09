<?php

namespace App\Modules\Ordering\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderNumberGenerator
{
    /**
     * Genera el siguiente número de pedido:
     * BVR-A1B2C3D4
     */
    public function next(): string
    {
        $prefix = 'BVR-';

        // Generar 8 caracteres alfanuméricos únicos
        $randomPart = Str::upper(Str::random(8));

        // Verificar que no exista (muy improbable, pero por seguridad)
        while (DB::table('orders')->where('order_number', $prefix.$randomPart)->exists()) {
            $randomPart = Str::upper(Str::random(8));
        }

        return $prefix.$randomPart;
    }
}
