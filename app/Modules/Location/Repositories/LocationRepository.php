<?php

namespace App\Modules\Location\Repositories;

use App\Models\Location;
use App\Modules\Location\Enums\LocationLevel;
use Illuminate\Database\Eloquent\Collection;

class LocationRepository
{
    /**
     * Obtener todos los departamentos.
     */
    public function getDepartments(): Collection
    {
        return Location::query()
            ->departments()
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtener las provincias de un departamento.
     */
    public function getProvinces(int $departmentId): Collection
    {
        return Location::query()
            ->provinces()
            ->where('parent_id', $departmentId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtener los distritos de una provincia.
     */
    public function getDistricts(int $provinceId): Collection
    {
        return Location::query()
            ->districts()
            ->where('parent_id', $provinceId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Buscar una ubicación por su ID.
     */
    public function find(int $id): ?Location
    {
        return Location::query()
            ->find($id);
    }

    /**
     * Buscar una ubicación por UBIGEO.
     */
    public function findByUbigeo(string $ubigeo): ?Location
    {
        return Location::query()
            ->where('ubigeo', $ubigeo)
            ->first();
    }

    /**
     * Obtener una ubicación con toda su jerarquía.
     *
     * Distrito → Provincia → Departamento
     */
    public function findWithHierarchy(int $id): ?Location
    {
        return Location::query()
            ->with([
                'parent.parent',
            ])
            ->find($id);
    }

    /**
     * Obtener ubicaciones por nivel.
     */
    public function getByLevel(LocationLevel $level): Collection
    {
        return Location::query()
            ->where('level', $level)
            ->orderBy('name')
            ->get();
    }
}
