<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BraveraMakeModule extends Command
{
    /**
     * Nombre del comando.
     */
    protected $signature = 'bravera:make-module {name}';

    /**
     * Descripción.
     */
    protected $description = 'Crear un módulo para la arquitectura Bravera';

    /**
     * Directorios del módulo.
     */
    protected array $directories = [
        'Actions',
        'DTO',
        'Enums',
        'Events',
        'Forms',
        'Jobs',
        'Listeners',
        'Livewire',
        'Policies',
        'Repositories',
        'Rules',
        'Services',
        'Traits',
        'ViewModels',
        'Views',
    ];

    /**
     * Archivos Blade.
     */
    protected array $views = [
        'index.blade.php',
        'table.blade.php',
        'form.blade.php',
    ];

    /**
     * Ejecutar comando.
     */
    public function handle(): int
    {
        $module = ucfirst($this->argument('name'));

        $basePath = app_path("Modules/{$module}");

        $this->newLine();

        $this->info("Creando módulo {$module}...");

        /*
        |--------------------------------------------------------------------------
        | Crear módulo
        |--------------------------------------------------------------------------
        */

        if (! File::exists($basePath)) {

            File::makeDirectory(
                $basePath,
                0755,
                true
            );

            $this->line("✔ Modules/{$module}");
        } else {

            $this->warn("• Modules/{$module} ya existe.");
        }

        /*
        |--------------------------------------------------------------------------
        | Directorios
        |--------------------------------------------------------------------------
        */

        foreach ($this->directories as $directory) {

            $path = "{$basePath}/{$directory}";

            if (! File::exists($path)) {

                File::makeDirectory(
                    $path,
                    0755,
                    true
                );

                $this->line("✔ {$directory}");
            } else {

                $this->warn("• {$directory} ya existe.");
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Vistas
        |--------------------------------------------------------------------------
        */

        foreach ($this->views as $view) {

            $path = "{$basePath}/Views/{$view}";

            if (! File::exists($path)) {

                File::put($path, '');

                $this->line("✔ Views/{$view}");
            } else {

                $this->warn("• Views/{$view} ya existe.");
            }
        }

        $this->newLine();

        $this->info("Módulo {$module} listo.");

        return self::SUCCESS;
    }
}
