<?php

namespace Tests\Feature\Meta;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\MetaConversionsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaConversionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_is_sent_once_with_shared_event_id_and_hashed_data(): void
    {
        $this->configureMeta();
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);

        [$order] = $this->orderWithItem(true);
        $service = app(MetaConversionsService::class);

        $this->assertTrue($service->purchase($order));

        $order->refresh();
        $this->assertSame('purchase_'.$order->order_number, $order->meta_purchase_event_id);
        $this->assertNotNull($order->meta_purchase_sent_at);

        Http::assertSent(function (Request $request) use ($order): bool {
            $event = $request->data()['data'][0] ?? [];

            return $event['event_name'] === 'Purchase'
                && $event['event_id'] === 'purchase_'.$order->order_number
                && $event['custom_data']['currency'] === 'PEN'
                && (float) $event['custom_data']['value'] === 199.90
                && $event['custom_data']['num_items'] === 2
                && $event['custom_data']['order_id'] === $order->order_number
                && $event['user_data']['em'][0] === hash('sha256', 'cliente@example.test')
                && $event['user_data']['ph'][0] === hash('sha256', '51987654321');
        });

        $this->assertFalse($service->purchase($order->fresh()));
        Http::assertSentCount(1);
    }

    public function test_purchase_is_not_sent_without_marketing_consent(): void
    {
        $this->configureMeta();
        Http::fake();

        [$order] = $this->orderWithItem(false);
        $service = app(MetaConversionsService::class);

        $this->assertFalse($service->purchase($order));
        $this->assertNull($order->fresh()->meta_purchase_sent_at);
        Http::assertNothingSent();
    }

    public function test_standard_events_are_forwarded_to_capi_with_their_event_ids(): void
    {
        $this->configureMeta();
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);
        request()->cookies->set('brevare_consent', 'all');

        $service = app(MetaConversionsService::class);
        $events = [
            'InitiateCheckout' => 'initiate_checkout_123',
            'AddPaymentInfo' => 'add_payment_info_123',
            'AddToCart' => 'add_to_cart_123_456_1_1234567890',
        ];

        foreach ($events as $name => $eventId) {
            $this->assertTrue($service->track($name, $eventId, [
                'currency' => 'PEN',
                'value' => 99.90,
                'content_ids' => ['SKU-META'],
                'content_type' => 'product',
            ]));
        }

        Http::assertSentCount(3);

        foreach ($events as $name => $eventId) {
            Http::assertSent(fn (Request $request): bool => data_get($request->data(), 'data.0.event_name') === $name
                && data_get($request->data(), 'data.0.event_id') === $eventId
                && data_get($request->data(), 'data.0.action_source') === 'website');
        }
    }

    public function test_layout_includes_pixel_configuration_and_consent_banner(): void
    {
        $this->configureMeta();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('BrevareMeta')
            ->assertSee('brevare-cookie-consent')
            ->assertSee('Aceptar todas')
            ->assertSee('autoConfig', false)
            ->assertSee('sentEventIds', false);
    }

    private function configureMeta(): void
    {
        config([
            'meta.pixel_enabled' => true,
            'meta.pixel_id' => '1234567890',
            'meta.capi_enabled' => true,
            'meta.access_token' => 'test-token',
            'meta.graph_version' => 'v23.0',
            'meta.test_event_code' => null,
        ]);
    }

    /**
     * @return array{0: Order}
     */
    private function orderWithItem(bool $consent): array
    {
        $user = User::create([
            'name' => 'Cliente Meta',
            'email' => 'cliente@example.test',
            'phone' => '987654321',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $order = Order::create([
            'order_number' => 'BVR-META-'.uniqid(),
            'user_id' => $user->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 199.90,
            'shipping_total' => 0,
            'discount_total' => 0,
            'total' => 199.90,
            'cost_total' => 0,
            'currency' => 'PEN',
            'marketing_consent' => $consent,
            'customer_snapshot' => [
                'name' => $user->name,
                'email' => $user->email,
                'meta_event_source_url' => 'https://brevare.com/checkout',
                'meta_client_ip' => '127.0.0.1',
                'meta_client_user_agent' => 'PHPUnit',
            ],
            'address_snapshot' => [
                'phone' => '987654321',
                'address' => 'Av. Prueba 123',
            ],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Producto Meta',
            'variant_sku' => 'SKU-META',
            'quantity' => 2,
            'unit_price' => 99.95,
            'unit_cost' => 0,
            'supplier_shipping_cost' => 0,
            'shipping_price' => 0,
            'line_subtotal' => 199.90,
            'line_cost_total' => 0,
        ]);

        return [$order->fresh('items')];
    }
}
