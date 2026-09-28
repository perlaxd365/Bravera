<?php

namespace App\Support\Generators;

use Illuminate\Support\Facades\File;

class ModuleGenerator
{
    public function __construct(
        protected string $module
    ) {}

    public function generate(): void
    {
        $basePath = app_path("Modules/{$this->module}");

        if (File::exists($basePath)) {
            throw new \Exception("El módulo {$this->module} ya existe.");
        }

        $directories = [
            '',
            'DTO',
            'Enums',
            'Forms',
            'Livewire',
            'Livewire/Components',
            'Livewire/Pages',
            'Livewire/Modals',
            'Repositories',
            'Services',
            'Policies',
            'Requests',
            'Traits',
            'Actions',
            'Events',
            'Listeners',
            'Jobs',
            'Rules',
            'ViewModels',
            'Views',
        ];

        foreach ($directories as $directory) {

            File::makeDirectory(
                $basePath.($directory ? "/{$directory}" : ''),
                0755,
                true
            );
        }

        (new DTOGenerator($this->module))->generate();

        (new RepositoryGenerator($this->module))->generate();
    }

    protected function createFromStub(
        string $stub,
        string $destination,
        array $replace
    ): void {

        $content = File::get(
            base_path("stubs/brevare/{$stub}")
        );

        foreach ($replace as $search => $value) {
            $content = str_replace($search, $value, $content);
        }

        File::put(
            app_path($destination),
            $content
        );
    }
}
