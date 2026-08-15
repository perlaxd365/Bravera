<?php

namespace App\Modules\Shipping\Services;

use App\DTOs\ShippingZoneDTO;
use App\Models\Location;
use App\Models\ShippingZone;
use App\Modules\Shipping\Enums\ShippingZoneType;
use App\Modules\Shipping\Repositories\ShippingZoneRepository;
use Illuminate\Validation\ValidationException;

class ShippingZoneService
{
    public function __construct(
        protected ShippingZoneRepository $repository
    ) {}

    /**
     * Obtener todas las zonas.
     */
    public function getAll()
    {
        return $this->repository->getAll();
    }

    /**
     * Obtener zonas activas.
     */
    public function getActive()
    {
        return $this->repository->getActive();
    }

    /**
     * Buscar una zona.
     */
    public function find(int $id): ?ShippingZone
    {
        return $this->repository->find($id);
    }

    /**
     * Crear una zona de envío.
     */
    public function create(ShippingZoneDTO $dto): ShippingZone
    {
        $this->validateLocationLevel(
            $dto->location_id,
            $dto->type
        );

        if (
            $this->repository->findByLocation(
                $dto->location_id,
                $dto->type
            )
        ) {
            throw ValidationException::withMessages([
                'location_id' => 'Ya existe una zona de envío para esta ubicación.',
            ]);
        }

        return $this->repository->create($dto);
    }

    /**
     * Actualizar una zona.
     */
    public function update(
        ShippingZone $zone,
        ShippingZoneDTO $dto
    ): ShippingZone {
        $this->validateLocationLevel(
            $dto->location_id,
            $dto->type
        );

        $existing = $this->repository->findByLocation(
            $dto->location_id,
            $dto->type
        );

        if (
            $existing &&
            $existing->id !== $zone->id
        ) {
            throw ValidationException::withMessages([
                'location_id' => 'Ya existe una zona de envío para esta ubicación.',
            ]);
        }

        return $this->repository->update(
            $zone,
            $dto
        );
    }

    /**
     * Activar o desactivar una zona.
     */
    public function toggleStatus(ShippingZone $zone): ShippingZone
    {
        return $this->repository->toggleStatus($zone);
    }

    /**
     * Eliminar una zona.
     */
    public function delete(ShippingZone $zone): void
    {
        $this->repository->delete($zone);
    }

    /**
     * Resolver la zona correspondiente a una ubicación.
     *
     * Distrito → Provincia → Departamento
     */
    public function resolveForLocation(
        Location $location
    ): ?ShippingZone {
        return $this->repository->findForLocation($location);
    }

    /**
     * Validar que el nivel de Location coincida
     * con el tipo de zona.
     */
    private function validateLocationLevel(
        int $locationId,
        ShippingZoneType $type
    ): void {
        $location = Location::find($locationId);

        if (! $location) {
            throw ValidationException::withMessages([
                'location_id' => 'La ubicación seleccionada no existe.',
            ]);
        }

        if ($location->level->value !== $type->value) {
            throw ValidationException::withMessages([
                'location_id' => sprintf(
                    'La ubicación seleccionada debe ser de nivel %s.',
                    $type->value
                ),
            ]);
        }
    }
}
