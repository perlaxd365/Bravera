<?php

namespace Tests\Feature;

use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * La portada responde correctamente con el catálogo de demo.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed();
        Artisan::call('db:seed', ['--class' => ProductSeeder::class]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
