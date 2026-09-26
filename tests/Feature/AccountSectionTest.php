<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/mi-cuenta/pedidos')
            ->assertOk()
            ->assertSee('Mis pedidos');

        $this->actingAs($user)
            ->get('/mi-cuenta/pedidos')
            ->assertOk()
            ->assertSee('Mi cuenta');
    }

    public function test_addresses_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/mi-cuenta/direcciones')
            ->assertOk()
            ->assertSee('Mis direcciones');
    }

    public function test_account_pages_require_authentication(): void
    {
        $this->get('/mi-cuenta/perfil')->assertRedirect(route('login'));
        $this->get('/mi-cuenta/pedidos')->assertRedirect(route('login'));
        $this->get('/mi-cuenta/direcciones')->assertRedirect(route('login'));
    }
}
