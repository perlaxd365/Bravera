<?php

namespace Tests\Feature\Store;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterPaymentMethodsTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_has_no_duplicate_legal_links_and_shows_payment_providers(): void
    {
        $response = $this->get(route('about'))->assertOk();

        $document = new \DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($response->getContent());
        libxml_clear_errors();

        $xpath = new \DOMXPath($document);

        $this->assertSame(
            1,
            $xpath->query('//footer//a[normalize-space(.)="Libro de Reclamaciones"]')->length,
        );
        $this->assertSame(
            1,
            $xpath->query('//footer//a[contains(@href, "politica-de-cambios-y-devoluciones")]')->length,
        );
        $this->assertSame(
            1,
            $xpath->query('//footer//a[contains(@href, "terminos-y-condiciones")]')->length,
        );
        $this->assertSame(
            1,
            $xpath->query('//footer//section[@aria-label="Métodos de pago"]')->length,
        );

        $response
            ->assertSee('Tarjetas, Yape y Plin')
            ->assertSee('Tarjeta de crédito o débito')
            ->assertSee('https://culqi.com/assets/images/shared/logos/culqiConNombre.svg', false)
            ->assertSee('https://cdn.simpleicons.org/mercadopago/00B1EA', false)
            ->assertSee('https://culqi.com/assets/images/shared/logos/visa.svg', false)
            ->assertSee('https://culqi.com/assets/images/shared/logos/yape.svg', false);
    }
}
