<?php

namespace App\Livewire\Forms;

use App\DTOs\ShippingZoneDTO;
use App\Models\ShippingZone;
use App\Modules\Shipping\Enums\ShippingZoneType;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ShippingZoneForm extends Form
{
    public ?int $id = null;

    #[Validate('required|string|max:150')]
    public string $name = '';

    #[Validate('required|in:department,province,district')]
    public string $type = '';

    #[Validate('required|integer|exists:locations,id')]
    public ?int $location_id = null;

    #[Validate('boolean')]
    public bool $status = true;

    /**
     * Limpia el formulario.
     */
    public function resetForm(): void
    {
        $this->reset();

        $this->type = '';
        $this->status = true;
    }

    /**
     * Carga una zona para edición.
     */
    public function fromModel(ShippingZone $zone): void
    {
        $this->id = $zone->id;
        $this->name = $zone->name;
        $this->type = $zone->type->value;
        $this->location_id = $zone->location_id;
        $this->status = $zone->status;
    }

    /**
     * Convierte el formulario a DTO.
     */
    public function toDto(): ShippingZoneDTO
    {
        return new ShippingZoneDTO(
            name: $this->name,
            type: ShippingZoneType::from($this->type),
            location_id: $this->location_id,
            status: $this->status,
        );
    }
}
