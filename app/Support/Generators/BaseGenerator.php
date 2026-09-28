<?php

namespace App\Support\Generators;

use App\Support\Generators\Contracts\GeneratorInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

abstract class BaseGenerator implements GeneratorInterface
{
    public function __construct(
        protected string $module
    ) {}

    protected function replacements(): array
    {
        return [

            '{{module}}' => $this->module,

            '{{model}}' => $this->module,

            '{{variable}}' => Str::camel($this->module),

            '{{snake}}' => Str::snake($this->module),

            '{{plural}}' => Str::plural(Str::snake($this->module)),

            '{{table}}' => Str::plural(Str::snake($this->module)),

            '{{kebab}}' => Str::kebab($this->module),

            '{{namespace}}' => "App\\Modules\\{$this->module}",

        ];
    }

    protected function createFromStub(
        string $stub,
        string $destination,
        array $replace = []
    ): void {

        $content = File::get(
            config('brevare.stubs_path')."/{$stub}"
        );

        foreach (
            array_merge(
                $this->replacements(),
                $replace
            ) as $search => $value
        ) {

            $content = str_replace(
                $search,
                $value,
                $content
            );
        }

        File::put(
            app_path($destination),
            $content
        );
    }
}
