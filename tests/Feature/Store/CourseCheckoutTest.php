<?php

namespace Tests\Feature\Store;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Livewire\Store\Checkout;
use App\Livewire\Store\ProductDetail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\CourseAsset;
use App\Models\CustomerAddress;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ShalomAgency;
use App\Models\User;
use App\Modules\Payments\Services\PaymentService;
use App\Modules\Product\Services\CourseProductService;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CourseCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_page_hides_internal_stock_without_embedding_legal_terms(): void
    {
        [$product, $variant] = $this->courseProduct();
        $variant->update([
            'compare_price' => 198,
            'discount_percent' => 50,
        ]);
        $this->assertSame('198.00', (string) $variant->fresh()->compare_price);

        $component = Livewire::test(ProductDetail::class, ['slug' => $product->slug]);
        $component
            ->assertSee('Producto digital · Disponible en tu cuenta')
            ->assertSee('Acceso de por vida')
            ->assertSee('Experiencia digital')
            ->assertSee('Precio de lanzamiento')
            ->assertSee('-50%')
            ->assertSee('Acceso digital de por vida')
            ->assertSee('Agregar curso al carrito')
            ->assertDontSee('Cantidad exacta')
            ->assertDontSee('1000000')
            ->assertDontSee('Producto digital sin devolución tras la entrega.')
            ->assertDontSee('Ver condiciones')
            ->assertSee('Acceso digital');

        $html = $component->html();
        $this->assertLessThan(
            strpos($html, 'Experiencia digital'),
            strpos($html, 'Precio del curso'),
        );

        $this->get(route('terms'))
            ->assertOk()
            ->assertSee('Política de no devolución')
            ->assertSee('archivos PDF');

        $this->get(route('returns'))
            ->assertOk()
            ->assertSee('no admiten cambios ni devoluciones');
    }

    public function test_course_variant_keeps_its_reference_price_when_the_product_is_updated(): void
    {
        [$product, $variant] = $this->courseProduct();
        $variant->update([
            'compare_price' => 198,
            'discount_percent' => 50,
        ]);

        $product->update(['course_price' => 79.20]);
        $updated = app(CourseProductService::class)->ensureDigitalVariant($product->fresh());

        $this->assertSame('198.00', (string) $updated->compare_price);
        $this->assertSame(60.0, (float) $updated->discount_percent);
        $this->assertSame('79.20', (string) $updated->sale_price);
    }

    public function test_course_video_is_rendered_between_description_and_specifications(): void
    {
        [$product] = $this->courseProduct();
        $product->update(['video_url' => 'https://example.com/course-preview.mp4']);

        $html = Livewire::test(ProductDetail::class, ['slug' => $product->slug])->html();
        $descriptionPosition = strpos($html, 'product-description-title');
        $videoPosition = strpos($html, 'product-video-title');
        $specificationsPosition = strpos($html, 'product-specifications-title');

        $this->assertNotFalse($descriptionPosition);
        $this->assertNotFalse($videoPosition);
        $this->assertNotFalse($specificationsPosition);
        $this->assertLessThan($videoPosition, $descriptionPosition);
        $this->assertLessThan($specificationsPosition, $videoPosition);
        $this->assertSame(1, substr_count($html, 'id="product-video-title"'));
    }

    public function test_course_purchase_completes_order_and_enables_private_assets(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        [$product, $variant] = $this->courseProduct();
        $file = CourseAsset::create([
            'product_id' => $product->id,
            'title' => 'Manual del curso',
            'type' => 'file',
            'file_path' => 'courses/'.$product->id.'/manual.pdf',
            'file_name' => 'manual.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2048,
            'is_active' => true,
        ]);
        Storage::disk('local')->put($file->file_path, 'course-pdf');

        $cart = Cart::create(['user_id' => $user->id, 'status' => 'active']);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'supplier_variant_id' => $variant->supplierVariants()->firstOrFail()->id,
            'quantity' => 1,
            'unit_price' => 99,
            'unit_cost' => 0,
            'supplier_shipping_cost' => 0,
        ]);

        $order = Order::create([
            'order_number' => 'BVR-CURSO-001',
            'user_id' => $user->id,
            'cart_id' => $cart->id,
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'subtotal' => 99,
            'shipping_total' => 0,
            'discount_total' => 0,
            'total' => 99,
            'cost_total' => 0,
            'currency' => 'PEN',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_sku' => $variant->sku,
            'quantity' => 1,
            'status' => OrderItemStatus::ACTIVE,
            'unit_price' => 99,
            'unit_cost' => 0,
            'supplier_shipping_cost' => 0,
            'shipping_price' => 0,
            'line_subtotal' => 99,
            'line_cost_total' => 0,
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => 'manual',
            'method' => 'card',
            'amount' => 99,
            'currency' => 'PEN',
            'status' => PaymentStatus::PENDING,
        ]);

        app(PaymentService::class)->markAsPaid($payment);

        $order->refresh();
        $this->assertSame(OrderStatus::COMPLETED, $order->status);
        $this->assertSame(PaymentStatus::PAID, $order->payment_status);
        $this->assertNotNull($order->completed_at);
        $this->assertNull($order->pickup_code);

        $this->actingAs($user)
            ->get(route('course.assets.open', ['order' => $order, 'asset' => $file]))
            ->assertOk()
            ->assertDownload('manual.pdf');
    }

    public function test_course_only_checkout_does_not_require_an_address(): void
    {
        config(['payments.default_gateway' => 'manual', 'payments.allow_demo_gateway' => true]);

        $user = User::factory()->create();
        [$product, $variant] = $this->courseProduct();
        $department = Location::create([
            'ubigeo' => '150000',
            'name' => 'Lima',
            'level' => 'department',
        ]);
        $province = Location::create([
            'ubigeo' => '150100',
            'name' => 'Lima',
            'level' => 'province',
            'parent_id' => $department->id,
        ]);
        $district = Location::create([
            'ubigeo' => '150101',
            'name' => 'Miraflores',
            'level' => 'district',
            'parent_id' => $province->id,
        ]);
        CustomerAddress::create([
            'user_id' => $user->id,
            'full_name' => $user->name,
            'phone' => '999888777',
            'location_id' => $district->id,
            'address' => 'Av. Siempre Viva 742',
            'is_default' => true,
        ]);
        ShalomAgency::create([
            'code' => 'SH-COURSE-TEST',
            'department' => 'LIMA',
            'province' => 'LIMA',
            'district' => 'MIRAFLORES',
            'address' => 'AV. LARCO 123',
            'modality' => 'Regular',
            'active' => true,
        ]);

        $this->actingAs($user);
        app(CartService::class)->add($variant->id);

        Livewire::test(Checkout::class)
            ->assertSee('Compra digital')
            ->assertDontSee('Recojo en agencia Shalom')
            ->assertDontSee('Envío pagado ahora')
            ->assertDontSee('Envío a pagar en Shalom')
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertSame(OrderStatus::COMPLETED, $order->status);
        $this->assertSame(PaymentStatus::PAID, $order->payment_status);
        $this->assertNull($order->address_snapshot);
    }

    public function test_course_assets_reject_orders_that_are_not_paid_or_belong_to_another_user(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $other = User::factory()->create();
        [$product] = $this->courseProduct();
        $asset = CourseAsset::create([
            'product_id' => $product->id,
            'title' => 'Plantilla',
            'type' => 'file',
            'file_path' => 'courses/'.$product->id.'/plantilla.xlsx',
            'file_name' => 'plantilla.xlsx',
            'is_active' => true,
        ]);
        Storage::disk('local')->put($asset->file_path, 'content');

        $order = Order::create([
            'order_number' => 'BVR-CURSO-002',
            'user_id' => $owner->id,
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'subtotal' => 99,
            'shipping_total' => 0,
            'discount_total' => 0,
            'total' => 99,
            'cost_total' => 0,
            'currency' => 'PEN',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'status' => OrderItemStatus::ACTIVE,
            'unit_price' => 99,
            'unit_cost' => 0,
            'supplier_shipping_cost' => 0,
            'shipping_price' => 0,
            'line_subtotal' => 99,
            'line_cost_total' => 0,
        ]);

        $this->actingAs($owner)
            ->get(route('course.assets.open', ['order' => $order, 'asset' => $asset]))
            ->assertForbidden();

        $this->actingAs($other)
            ->get(route('course.assets.open', ['order' => $order, 'asset' => $asset]))
            ->assertForbidden();
    }

    private function courseProduct(): array
    {
        $category = Category::create([
            'name' => 'Cursos',
            'slug' => 'cursos',
            'path' => 'cursos',
            'is_visible' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Curso de prueba',
            'slug' => 'curso-de-prueba',
            'short_description' => 'Curso digital.',
            'description' => 'Contenido completo del curso.',
            'product_type' => ProductType::COURSE,
            'course_price' => 99,
            'status' => true,
            'is_visible' => true,
        ]);
        $variant = app(CourseProductService::class)->ensureDigitalVariant($product);

        return [$product->fresh(), $variant];
    }
}
