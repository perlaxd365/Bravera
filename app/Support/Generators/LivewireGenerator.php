<?php

namespace App\Support\Generators;

class LivewireGenerator extends BaseGenerator
{
    public function generate(): void
    {
        $this->createFromStub(
            'livewire-index.stub',

            "Modules/{$this->module}/Livewire/{$this->module}Index.php",

            [

                '{{module}}' => $this->module,

                '{{lower}}' => strtolower($this->module),

                '{{plural}}' => strtolower($this->module).'s',

            ]
        );
    }
}
