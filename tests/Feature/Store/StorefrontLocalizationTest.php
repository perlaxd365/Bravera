<?php

namespace Tests\Feature\Store;

use App\Enums\ProductType;
use App\Models\Category;
use App\Models\CourseAsset;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Modules\Product\Services\CourseProductService;
use App\Support\Localization\StorefrontContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class StorefrontLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_united_states_detection_uses_english_and_usd(): void
    {
        $this->get('/', [
            'CF-IPCountry' => 'US',
            'Accept-Language' => 'en-US,en;q=0.9',
        ])
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('Guaranteed delivery')
            ->assertSee('USD $');
    }

    public function test_peru_defaults_to_spanish_and_pen(): void
    {
        $this->get('/', [
            'CF-IPCountry' => 'PE',
            'Accept-Language' => 'es-PE,es;q=0.9',
        ])
            ->assertOk()
            ->assertSee('lang="es"', false)
            ->assertSee('Entrega garantizada')
            ->assertSee('PEN S/');
    }

    public function test_manual_language_choice_overrides_geolocation_without_changing_currency(): void
    {
        $this->withCookie('brevare_locale', 'es')
            ->get('/', [
                'CF-IPCountry' => 'US',
                'Accept-Language' => 'en-US',
            ])
            ->assertOk()
            ->assertSee('lang="es"', false)
            ->assertSee('USD $');
    }

    public function test_exchange_rate_is_fixed_at_three_point_four_nine(): void
    {
        config(['storefront.usd_rate' => 3.49]);

        $request = Request::create('/');
        $request->headers->set('CF-IPCountry', 'CL');
        $request->headers->set('Accept-Language', 'es-CL,es;q=0.9');
        $context = StorefrontContext::fromRequest($request);

        $this->assertSame('es', $context->locale);
        $this->assertSame('USD', $context->currency);
        $this->assertSame(28.65, $context->displayAmount(100.00));
        $this->assertSame('S/ 100.00', $context->chargedFromPen(100.00));
    }

    public function test_course_has_english_content_and_language_specific_assets(): void
    {
        $category = Category::create([
            'name' => 'Cursos',
            'slug' => 'cursos-localizados',
            'path' => 'cursos-localizados',
            'is_visible' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Curso en español',
            'slug' => 'curso-bilingue',
            'short_description' => 'Resumen en español.',
            'description' => 'Contenido en español.',
            'product_type' => ProductType::COURSE,
            'course_price' => 100,
            'status' => true,
            'is_visible' => true,
        ]);
        ProductTranslation::create([
            'product_id' => $product->id,
            'locale' => 'en',
            'name' => 'English course',
            'short_description' => 'English summary.',
            'description' => 'English content.',
        ]);
        CourseAsset::create([
            'product_id' => $product->id,
            'locale' => 'es',
            'title' => 'Guía',
            'type' => 'link',
            'url' => 'https://example.com/guia',
        ]);
        CourseAsset::create([
            'product_id' => $product->id,
            'locale' => 'en',
            'title' => 'Guide',
            'type' => 'link',
            'url' => 'https://example.com/guide',
        ]);
        app(CourseProductService::class)->ensureDigitalVariant($product);

        app()->setLocale('en');
        $product = $product->fresh(['translations', 'courseAssets']);

        $this->assertSame('English course', $product->localized('name'));
        $this->assertSame('English summary.', $product->localized('short_description'));
        $this->assertSame(['Guide'], $product->courseAssetsForLocale('en')->pluck('title')->all());

        app()->setLocale('es');
        $this->assertSame('Curso en español', $product->fresh('translations')->localized('name'));
    }

    public function test_english_product_page_displays_usd_and_explains_the_pen_charge(): void
    {
        $category = Category::create([
            'name' => 'Courses',
            'slug' => 'courses-page',
            'path' => 'courses-page',
            'is_visible' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Curso',
            'slug' => 'curso-usd',
            'product_type' => ProductType::COURSE,
            'course_price' => 100,
            'status' => true,
            'is_visible' => true,
        ]);
        ProductTranslation::create([
            'product_id' => $product->id,
            'locale' => 'en',
            'name' => 'English course',
            'short_description' => 'Learn with us.',
        ]);
        app(CourseProductService::class)->ensureDigitalVariant($product);

        $this->get(route('store.product', ['slug' => $product->slug]), [
            'CF-IPCountry' => 'US',
            'Accept-Language' => 'en-US',
        ])
            ->assertOk()
            ->assertSee('English course')
            ->assertSee('$28.65 USD')
            ->assertSee('You will be charged S/ 100.00 in Peruvian soles (PEN).');
    }
}
