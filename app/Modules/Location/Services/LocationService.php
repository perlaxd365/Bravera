<?php

namespace App\Modules\Location\Services;

use App\Models\Location;
use App\Modules\Location\Enums\LocationLevel;
use App\Modules\Location\Repositories\LocationRepository;
use Illuminate\Database\Eloquent\Collection;

class LocationService
{
    public function __construct(
        protected LocationRepository $repository
    ) {}

    /**
     * Obtener todos los departamentos.
     */
    public function getDepartments(): Collection
    {
        return $this->repository->getDepartments();
    }

    /**
     * Obtener las provincias de un departamento.
     */
    public function getProvinces(int $departmentId): Collection
    {
        return $this->repository->getProvinces($departmentId);
    }

    /**
     * Obtener los distritos de una provincia.
     */
    public function getDistricts(int $provinceId): Collection
    {
        return $this->repository->getDistricts($provinceId);
    }

    /**
     * Buscar una ubicación.
     */
    public function find(int $id): ?Location
    {
        return $this->repository->find($id);
    }

    /**
     * Buscar una ubicación por UBIGEO.
     */
    public function findByUbigeo(string $ubigeo): ?Location
    {
        return $this->repository->findByUbigeo($ubigeo);
    }

    /**
     * Obtener una ubicación con su jerarquía.
     */
    public function findWithHierarchy(int $id): ?Location
    {
        return $this->repository->findWithHierarchy($id);
    }

    /**
     * Obtener ubicaciones por nivel.
     */
    public function getByLevel(LocationLevel $level): Collection
    {
        return $this->repository->getByLevel($level);
    }
}
