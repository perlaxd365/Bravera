<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\SupplierDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSupplierRequest;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;

class SupplierController extends Controller
{
    public function store(StoreSupplierRequest $request, SupplierService $suppliers): JsonResponse
    {
        $data = $request->validated();
        $supplier = $suppliers->create(new SupplierDTO(
            code: null,
            business_name: (string) $data['business_name'],
            trade_name: $data['trade_name'] ?? null,
            tax_id: $data['tax_id'] ?? null,
            contact_name: $data['contact_name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            whatsapp: $data['whatsapp'] ?? null,
            website: $data['website'] ?? null,
            location_id: isset($data['location_id']) ? (int) $data['location_id'] : null,
            address: $data['address'] ?? null,
            estimated_dispatch_days: (int) ($data['estimated_dispatch_days'] ?? 1),
            status: (string) ($data['status'] ?? 'active'),
            internal_notes: $data['internal_notes'] ?? null,
        ));

        return response()->json([
            'message' => 'Proveedor creado correctamente.',
            'data' => [
                'id' => $supplier->id,
                'code' => $supplier->code,
                'business_name' => $supplier->business_name,
                'trade_name' => $supplier->trade_name,
                'status' => $supplier->status,
            ],
        ], 201);
    }
}
