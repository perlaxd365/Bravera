<?php

namespace App\Console\Commands\Brevare;

use App\Support\Generators\ModuleGenerator;
use Illuminate\Console\Command;

class MakeModuleCommand extends Command
{
    protected $signature = 'brevare:make-module {name}';

    protected $description = 'Genera un módulo completo para Brevare';

    public function handle(): int
    {
        try {

            $generator = new ModuleGenerator(
                $this->argument('name')
            );

            $generator->generate();

            $this->info('✔ Módulo generado correctamente.');

            return self::SUCCESS;
        } catch (\Throwable $e) {

            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
