<?php

namespace App\Support\Generators;

class RepositoryGenerator extends BaseGenerator
{
    public function generate(): void
    {
        $this->createRepository();

        $this->createInterface();
    }

    protected function createRepository(): void
    {
        $this->createFromStub(

            'repository.stub',

            "Modules/{$this->module}/Repositories/{$this->module}Repository.php"

        );
    }

    protected function createInterface(): void
    {
        $this->createFromStub(

            'repository-interface.stub',

            "Modules/{$this->module}/Repositories/{$this->module}RepositoryInterface.php"

        );
    }
}
