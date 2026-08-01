<?php

namespace App\Livewire\Forms;

use App\DTOs\SupplierDTO;
use App\Models\Supplier;
use Livewire\Attributes\Validate;
use Livewire\Form;

class SupplierForm extends Form
{
    public ?int $id = null;

    #[Validate('required|string|max:200')]
    public string $business_name = '';

    #[Validate('nullable|string|max:200')]
    public ?string $trade_name = null;

    #[Validate('nullable|string|max:20')]
    public ?string $tax_id = null;

    #[Validate('nullable|string|max:150')]
    public ?string $contact_name = null;

    #[Validate('nullable|email|max:150')]
    public ?string $email = null;

    #[Validate('nullable|string|max:30')]
    public ?string $phone = null;

    #[Validate('nullable|string|max:30')]
    public ?string $whatsapp = null;

    #[Validate('nullable|url|max:255')]
    public ?string $website = null;

    #[Validate('nullable|string|max:100')]
    public ?string $department = null;

    #[Validate('nullable|string|max:100')]
    public ?string $province = null;

    #[Validate('nullable|string|max:100')]
    public ?string $district = null;

    #[Validate('nullable|string|max:255')]
    public ?string $address = null;

    #[Validate('required|integer|min:1|max:90')]
    public int $estimated_dispatch_days = 1;

    #[Validate('string')]
    public string $status = 'active';

    #[Validate('nullable|string')]
    public ?string $internal_notes = null;

    /**
     * Limpia el formulario.
     */
    public function resetForm(): void
    {
        $this->reset();

        $this->estimated_dispatch_days = 1;
        $this->status = 'active';
    }

    /**
     * Carga un proveedor para edición.
     */
    public function fromModel(Supplier $supplier): void
    {
        $this->id = $supplier->id;

        $this->business_name = $supplier->business_name;
        $this->trade_name = $supplier->trade_name;
        $this->tax_id = $supplier->tax_id;
        $this->contact_name = $supplier->contact_name;
        $this->email = $supplier->email;
        $this->phone = $supplier->phone;
        $this->whatsapp = $supplier->whatsapp;
        $this->website = $supplier->website;
        $this->department = $supplier->department;
        $this->province = $supplier->province;
        $this->district = $supplier->district;
        $this->address = $supplier->address;
        $this->estimated_dispatch_days = $supplier->estimated_dispatch_days;
        $this->status = $supplier->status;
        $this->internal_notes = $supplier->internal_notes;
    }

    /**
     * Convierte el formulario a DTO.
     */
    public function toDto(): SupplierDTO
    {
        return new SupplierDTO(
            code: null,
            business_name: $this->business_name,
            trade_name: $this->trade_name,
            tax_id: $this->tax_id,
            contact_name: $this->contact_name,
            email: $this->email,
            phone: $this->phone,
            whatsapp: $this->whatsapp,
            website: $this->website,
            department: $this->department,
            province: $this->province,
            district: $this->district,
            address: $this->address,
            estimated_dispatch_days: $this->estimated_dispatch_days,
            status: $this->status,
            internal_notes: $this->internal_notes,
        );
    }
}
