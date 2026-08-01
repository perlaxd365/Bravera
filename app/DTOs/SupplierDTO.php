<?php

namespace App\DTOs;

class SupplierDTO
{
    public function __construct(
        public readonly ?string $code,
        public readonly string $business_name,
        public readonly ?string $trade_name,
        public readonly ?string $tax_id,
        public readonly ?string $contact_name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $whatsapp,
        public readonly ?string $website,
        public readonly ?string $department,
        public readonly ?string $province,
        public readonly ?string $district,
        public readonly ?string $address,
        public readonly int $estimated_dispatch_days,
        public readonly string $status,
        public readonly ?string $internal_notes,
        public readonly ?int $created_by = null,
        public readonly ?int $updated_by = null,
    ) {}

    /**
     * Convierte el DTO en un arreglo.
     */
    public function toArray(): array
    {
        return [
            'code'                      => $this->code,
            'business_name'             => $this->business_name,
            'trade_name'                => $this->trade_name,
            'tax_id'                    => $this->tax_id,
            'contact_name'              => $this->contact_name,
            'email'                     => $this->email,
            'phone'                     => $this->phone,
            'whatsapp'                  => $this->whatsapp,
            'website'                   => $this->website,
            'department'                => $this->department,
            'province'                  => $this->province,
            'district'                  => $this->district,
            'address'                   => $this->address,
            'estimated_dispatch_days'   => $this->estimated_dispatch_days,
            'status'                    => $this->status,
            'internal_notes'            => $this->internal_notes,
            'created_by'                => $this->created_by,
            'updated_by'                => $this->updated_by,
        ];
    }
}
