<?php

namespace App\Console\Commands;

use App\Models\ShalomAgency;
use App\Modules\Shipping\Services\NominatimGeocoder;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportShalomAgencies extends Command
{
    protected $signature = 'shalom:import
        {file? : Ruta del CSV de agencias}
        {--geocode : Geocodificar las agencias sin coordenadas}
        {--delay=1100 : Milisegundos entre solicitudes a Nominatim}
        {--department= : Limitar la geocodificación a un departamento}';

    protected $description = 'Importa las agencias Shalom (modalidad Regular) y opcionalmente las geocodifica';

    public function handle(NominatimGeocoder $geocoder): int
    {
        $file = $this->argument('file') ?: database_path('data/shalom_agencies.csv');

        if (! is_file($file) || ! is_readable($file)) {
            $this->error("No se encontró el CSV: {$file}");

            return self::FAILURE;
        }

        $handle = fopen($file, 'rb');

        if ($handle === false) {
            $this->error('No se pudo abrir el CSV.');

            return self::FAILURE;
        }

        $header = fgetcsv($handle);
        $header[0] = ltrim((string) ($header[0] ?? ''), "\xEF\xBB\xBF");

        $columns = array_flip(array_map(
            fn ($value) => mb_strtolower(trim((string) $value)),
            $header ?: []
        ));

        $required = ['departamento', 'provincia', 'distrito', 'agencia / dirección', 'modalidad'];

        foreach ($required as $column) {
            if (! array_key_exists($column, $columns)) {
                fclose($handle);
                $this->error("Falta la columna [{$column}] en el CSV.");

                return self::FAILURE;
            }
        }

        $imported = 0;
        $updated = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $modality = $this->clean($row[$columns['modalidad']] ?? '');

            // La tienda trabaja solo con la modalidad Regular.
            if (! str_contains(mb_strtolower($modality), 'regular')) {
                continue;
            }

            $department = $this->clean($row[$columns['departamento']] ?? '');
            $province = $this->clean($row[$columns['provincia']] ?? '');
            $district = $this->clean($row[$columns['distrito']] ?? '');
            $address = $this->clean($row[$columns['agencia / dirección']] ?? '');

            if ($department === '' || $province === '' || $district === '') {
                continue;
            }

            if ($address === '') {
                $address = $district;
            }

            $code = 'SH-'.substr(sha1(implode('|', [
                Str::upper(Str::ascii($department)),
                Str::upper(Str::ascii($province)),
                Str::upper(Str::ascii($district)),
                Str::upper(Str::ascii($address)),
            ])), 0, 16);

            $agency = ShalomAgency::query()->updateOrCreate(
                ['code' => $code],
                [
                    'department' => $department,
                    'province' => $province,
                    'district' => $district,
                    'address' => $address,
                    'modality' => 'Regular',
                    'active' => true,
                ]
            );

            $agency->wasRecentlyCreated ? $imported++ : $updated++;
        }

        fclose($handle);

        $this->info("Agencias importadas: {$imported}; actualizadas: {$updated}.");
        $this->info('Total en base de datos: '.ShalomAgency::query()->count().'.');

        if (! $this->option('geocode')) {
            return self::SUCCESS;
        }

        $query = ShalomAgency::query()
            ->where(function ($query) {
                $query->whereNull('latitude')->orWhereNull('longitude');
            });

        if ($this->option('department')) {
            $query->whereRaw(
                'UPPER(department) = ?',
                [mb_strtoupper(trim((string) $this->option('department')))]
            );
        }

        $pending = $query
            ->orderByRaw("CASE WHEN UPPER(department) = 'LIMA' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No hay agencias pendientes de geocodificar.');

            return self::SUCCESS;
        }

        $this->info("Geocodificando {$pending->count()} agencias...");
        $bar = $this->output->createProgressBar($pending->count());
        $bar->start();
        $ok = 0;

        foreach ($pending as $agency) {
            if ($geocoder->geocodeAgency($agency)) {
                $ok++;
            }

            $bar->advance();

            if ($this->option('delay') > 0) {
                usleep((int) $this->option('delay') * 1000);
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Geocodificadas: {$ok}/{$pending->count()}.");

        return self::SUCCESS;
    }

    private function clean(mixed $value): string
    {
        return mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string) $value) ?? ''));
    }
}
