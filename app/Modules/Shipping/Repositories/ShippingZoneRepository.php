<?php

namespace App\Modules\Shipping\Repositories;

use App\DTOs\ShippingZoneDTO;
use App\Models\Location;
use App\Models\ShippingZone;
use App\Modules\Shipping\Enums\ShippingZoneType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ShippingZoneRepository
{
    /**
     * Obtiene un listado paginado.
     */
    public function paginate(
        int $perPage = 10,
        ?string $search = null
    ): LengthAwarePaginator {
        return ShippingZone::query()
            ->with('location')
            ->when($search, function ($query) use ($search) {
                $search = trim($search);

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhereHas('location', function ($location) use ($search) {
                            $location->where(
                                'name',
                                'like',
                                "%{$search}%"
                            );
                        });
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Obtener todas las zonas.
     */
    public function getAll(): Collection
    {
        return ShippingZone::query()
            ->with('location')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtener zonas activas.
     */
    public function getActive(): Collection
    {
        return ShippingZone::query()
            ->active()
            ->with('location')
            ->orderBy('name')
            ->get();
    }

    /**
     * Buscar una zona por ID.
     */
    public function find(int $id): ?ShippingZone
    {
        return ShippingZone::query()
            ->with('location')
            ->find($id);
    }

    /**
     * Buscar una zona asociada a una ubicación y tipo.
     *
     * Se buscan también las zonas inactivas para evitar
     * duplicados al crear o actualizar.
     */
    public function findByLocation(
        int $locationId,
        ShippingZoneType $type
    ): ?ShippingZone {
        return ShippingZone::query()
            ->where('location_id', $locationId)
            ->where('type', $type)
            ->first();
    }

    /**
     * Crear una zona.
     */
    public function create(ShippingZoneDTO $dto): ShippingZone
    {
        return ShippingZone::create(
            $dto->toArray()
        );
    }

    /**
     * Actualizar una zona.
     */
    public function update(
        ShippingZone $zone,
        ShippingZoneDTO $dto
    ): ShippingZone {
        $zone->update(
            $dto->toArray()
        );

        return $zone->refresh();
    }

    /**
     * Cambiar estado de una zona.
     */
    public function toggleStatus(
        ShippingZone $zone
    ): ShippingZone {
        $zone->update([
            'status' => ! $zone->status,
        ]);

        return $zone->refresh();
    }

    /**
     * Eliminar una zona.
     */
    public function delete(
        ShippingZone $zone
    ): void {
        $zone->delete();
    }

    /**
     * Obtener la zona más específica para una ubicación.
     *
     * Prioridad:
     * Distrito → Provincia → Departamento
     */
    public function findForLocation(
        Location $location
    ): ?ShippingZone {
        $location->load('parent.parent');

        $current = $location;

        while ($current) {
            $zone = ShippingZone::query()
                ->active()
                ->where('location_id', $current->id)
                ->first();

            if ($zone) {
                return $zone;
            }

            $current = $current->parent;
        }

        return null;
    }
}
