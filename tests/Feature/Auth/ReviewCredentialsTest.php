<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La tienda publica las credenciales de la cuenta de prueba (validación de Culqi)
 * en el login y en la portada, mientras `brevare.review_login.enabled` esté activo.
 */
class ReviewCredentialsTest extends TestCase
{
    use RefreshDatabase;

    private const ENABLED = [
        'enabled' => true,
        'email' => 'revision@brevare.com',
        'password' => 'Brevare2026Culqi',
    ];

    private const DISABLED = [
        'enabled' => false,
        'email' => 'revision@brevare.com',
        'password' => 'Brevare2026Culqi',
    ];

    public function test_el_login_muestra_las_credenciales_de_prueba(): void
    {
        config()->set('brevare.review_login', self::ENABLED);

        $this->get('/login')
            ->assertOk()
            ->assertSee('¿Vas a hacer pruebas de compra?')
            ->assertSee('revision@brevare.com')
            ->assertSee('Brevare2026Culqi');
    }

    public function test_la_portada_muestra_las_credenciales_de_prueba(): void
    {
        config()->set('brevare.review_login', self::ENABLED);

        $this->get('/')
            ->assertOk()
            ->assertSee('¿Vas a hacer pruebas de compra?')
            ->assertSee('revision@brevare.com')
            ->assertSee('Brevare2026Culqi');
    }

    public function test_se_ocultan_en_login_y_portada_al_desactivarlas(): void
    {
        config()->set('brevare.review_login', self::DISABLED);

        foreach (['/login', '/'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee('¿Vas a hacer pruebas de compra?')
                ->assertDontSee('Brevare2026Culqi');
        }
    }
}
