<?php

namespace App\Support\Generators;

class DTOGenerator extends BaseGenerator
{
    public function generate(): void
    {
        $this->createFromStub(

            'dto.stub',

            "Modules/{$this->module}/DTO/{$this->module}Data.php",

            [

                '{{class}}' => "{$this->module}Data",

            ]

        );
    }
}
