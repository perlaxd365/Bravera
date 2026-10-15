<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

/**
 * El login publica las credenciales de la cuenta de prueba (validación de Culqi)
 * mientras `brevare.review_login.enabled` esté activo.
 */
class LoginReviewCredentialsTest extends TestCase
{
    public function test_muestra_las_credenciales_de_prueba_cuando_esta_habilitado(): void
    {
        config()->set('brevare.review_login', [
            'enabled' => true,
            'email' => 'revision@brevare.com',
            'password' => 'Brevare2026Culqi',
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertSee('¿Vas a hacer pruebas de compra?')
            ->assertSee('revision@brevare.com')
            ->assertSee('Brevare2026Culqi');
    }

    public function test_oculta_las_credenciales_cuando_se_desactiva(): void
    {
        config()->set('brevare.review_login', [
            'enabled' => false,
            'email' => 'revision@brevare.com',
            'password' => 'Brevare2026Culqi',
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('¿Vas a hacer pruebas de compra?')
            ->assertDontSee('Brevare2026Culqi');
    }
}
