<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Modules\Location\Enums\LocationLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LocationSeeder extends Seeder
{
    /**
     * Ejecutar el seeder.
     */
    public function run(): void
    {
        $basePath = database_path('data/locations');

        $departmentsFile = $basePath.'/1_ubigeo_departamentos.csv';
        $provincesFile = $basePath.'/2_ubigeo_provincias.csv';
        $districtsFile = $basePath.'/3_ubigeo_distritos.csv';

        $this->validateFiles([
            $departmentsFile,
            $provincesFile,
            $districtsFile,
        ]);

        DB::transaction(function () use (
            $departmentsFile,
            $provincesFile,
            $districtsFile
        ) {
            $this->seedDepartments($departmentsFile);
            $this->seedProvinces($provincesFile);
            $this->seedDistricts($districtsFile);
        });

        $this->command->info(
            'Catálogo de ubicaciones cargado correctamente.'
        );
    }

    /**
     * Cargar departamentos.
     */
    private function seedDepartments(string $file): void
    {
        foreach ($this->readCsv($file) as $row) {
            Location::updateOrCreate(
                [
                    'ubigeo' => trim($row['ubigeo']),
                ],
                [
                    'name' => trim($row['departamento']),
                    'level' => LocationLevel::DEPARTMENT,
                    'parent_id' => null,
                ]
            );
        }
    }

    /**
     * Cargar provincias.
     */
    private function seedProvinces(string $file): void
    {
        foreach ($this->readCsv($file) as $row) {
            $provinceUbigeo = trim($row['ubigeo']);

            /*
             * Los dos primeros dígitos del UBIGEO
             * corresponden al departamento.
             *
             * Ejemplo:
             *
             * Provincia: 0101
             * Departamento: 01
             */
            $departmentUbigeo = substr($provinceUbigeo, 0, 2);

            $department = Location::query()
                ->where('ubigeo', $departmentUbigeo)
                ->where('level', LocationLevel::DEPARTMENT)
                ->first();

            if (! $department) {
                throw new RuntimeException(
                    "No se encontró el departamento con UBIGEO {$departmentUbigeo}."
                );
            }

            Location::updateOrCreate(
                [
                    'ubigeo' => $provinceUbigeo,
                ],
                [
                    'name' => trim($row['provincia']),
                    'level' => LocationLevel::PROVINCE,
                    'parent_id' => $department->id,
                ]
            );
        }
    }

    /**
     * Cargar distritos.
     */
    private function seedDistricts(string $file): void
    {
        foreach ($this->readCsv($file) as $row) {
            $districtUbigeo = trim($row['ubigeo']);

            /*
             * Los cuatro primeros dígitos del UBIGEO
             * corresponden a la provincia.
             *
             * Ejemplo:
             *
             * Distrito: 010101
             * Provincia: 0101
             */
            $provinceUbigeo = substr($districtUbigeo, 0, 4);

            $province = Location::query()
                ->where('ubigeo', $provinceUbigeo)
                ->where('level', LocationLevel::PROVINCE)
                ->first();

            if (! $province) {
                throw new RuntimeException(
                    "No se encontró la provincia con UBIGEO {$provinceUbigeo}."
                );
            }

            Location::updateOrCreate(
                [
                    'ubigeo' => $districtUbigeo,
                ],
                [
                    'name' => trim($row['distrito']),
                    'level' => LocationLevel::DISTRICT,
                    'parent_id' => $province->id,
                ]
            );
        }
    }

    /**
     * Leer archivo CSV.
     */
    private function readCsv(string $file): iterable
    {
        $handle = fopen($file, 'r');

        if ($handle === false) {
            throw new RuntimeException(
                "No se pudo abrir el archivo: {$file}"
            );
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            throw new RuntimeException(
                "El archivo CSV está vacío: {$file}"
            );
        }

        $headers = array_map(
            static fn ($header) => trim($header),
            $headers
        );

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                continue;
            }

            yield array_combine($headers, $row);
        }

        fclose($handle);
    }

    /**
     * Verificar que existan los archivos.
     */
    private function validateFiles(array $files): void
    {
        foreach ($files as $file) {
            if (! file_exists($file)) {
                throw new RuntimeException(
                    "No se encontró el archivo de datos: {$file}"
                );
            }
        }
    }
}
