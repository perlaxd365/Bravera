<?php

namespace App\Livewire\Store;

use App\Models\Coupon;
use App\Models\CustomerAddress;
use App\Modules\Discounts\Services\CouponService;
use App\Modules\Location\Services\LocationService;
use App\Modules\Ordering\Data\CheckoutData;
use App\Modules\Ordering\Services\OrderService;
use App\Modules\Payments\Services\PaymentService;
use App\Modules\Shipping\Services\ShippingQuoteService;
use App\Services\CartService;
use App\Services\CustomerAddressService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('store.layouts.app')]
class Checkout extends Component
{
    public bool $authRequired = false;

    public ?int $selectedAddressId = null;

    public bool $showNewAddress = false;

    public $addresses;

    public array $departments = [];

    public array $provinces = [];

    public array $districts = [];

    public string $newFullName = '';

    public string $newPhone = '';

    public ?int $newDepartmentId = null;

    public ?int $newProvinceId = null;

    public ?int $newDistrictId = null;

    public string $newAddress = '';

    public string $newReference = '';

    public string $couponCode = '';

    /**
     * Solo lectura para el cliente: el descuento real se recalcula en el
     * servidor contra la base de datos, nunca se toma de esta propiedad.
     */
    #[Locked]
    public ?array $couponApplied = null;

    public ?string $couponError = null;

    public string $paymentMethod = 'card';

    public string $notes = '';

    /**
     * Solo lectura para el cliente: la tarifa de envío real se recalcula en
     * el servidor contra la base de datos, nunca se toma de esta propiedad.
     */
    #[Locked]
    public ?array $quote = null;

    protected function queryString()
    {
        return ['couponCode' => ['except' => '']];
    }

    public function mount(CartService $cart)
    {
        $this->authRequired = ! auth()->check();

        if ($this->authRequired) {
            session()->flash('login_intent', 'Inicia sesión para completar tu compra.');

            return;
        }

        if ($cart->isEmpty()) {
            return redirect()->route('store.cart');
        }

        $this->addresses = new Collection;
    }

    public function updatedNewDepartmentId($value): void
    {
        $this->newProvinceId = null;
        $this->newDistrictId = null;
        $this->provinces = [];
        $this->districts = [];

        if ($value) {
            $this->provinces = app(LocationService::class)->getProvinces((int) $value)->toArray();
        }
    }

    public function updatedNewProvinceId($value): void
    {
        $this->newDistrictId = null;
        $this->districts = [];

        if ($value) {
            $this->districts = app(LocationService::class)->getDistricts((int) $value)->toArray();
        }
    }

    public function openNewAddress(): void
    {
        $this->showNewAddress = true;

        if ($this->departments === []) {
            $this->departments = app(LocationService::class)->getDepartments()->toArray();
        }
    }

    public function saveNewAddress(): void
    {
        $this->validate([
            'newFullName' => ['required', 'string', 'max:150'],
            'newPhone' => ['required', 'string', 'max:30'],
            'newDistrictId' => ['required', 'integer', 'exists:locations,id'],
            'newAddress' => ['required', 'string', 'max:255'],
            'newReference' => ['nullable', 'string', 'max:255'],
        ]);

        $address = app(CustomerAddressService::class)->create(auth()->user(), [
            'full_name' => $this->newFullName,
            'phone' => $this->newPhone,
            'location_id' => $this->newDistrictId,
            'address' => $this->newAddress,
            'reference' => $this->newReference ?: null,
            'is_default' => false,
        ]);

        $this->selectedAddressId = (int) $address->id;
        $this->showNewAddress = false;
        $this->couponApplied = null;
        $this->refreshQuote();
    }

    public function selectAddress(int $id): void
    {
        $owned = CustomerAddress::where('user_id', auth()->id())->whereKey($id)->exists();

        $this->selectedAddressId = $owned ? $id : null;
        $this->quote = null;
        $this->couponError = null;
    }

    public function applyCoupon(): void
    {
        $this->couponError = null;

        if (blank($this->couponCode)) {
            return;
        }

        $this->resolveTotals($this->selectedAddress());
    }

    public function removeCoupon(): void
    {
        $this->couponCode = '';
        $this->couponError = null;
        $this->couponApplied = null;
    }

    private function subtotal($items): float
    {
        return round($items->sum(fn ($item) => (float) $item->unit_price * $item->quantity), 2);
    }

    /**
     * Dirección seleccionada, verificada contra el usuario autenticado.
     */
    private function selectedAddress(): ?CustomerAddress
    {
        if (! $this->selectedAddressId) {
            return null;
        }

        return CustomerAddress::with('location')
            ->where('user_id', auth()->id())
            ->find($this->selectedAddressId);
    }

    /**
     * Recalcula en el servidor todos los importes del checkout leyendo la base
     * de datos, y deja el resultado en las propiedades que consume la vista.
     *
     * Las propiedades $quote y $couponApplied son de solo lectura para el
     * cliente (#[Locked]): el navegador no puede alterarlas, y aunque lo
     * intentara, el pedido se persiste con los valores de esta función.
     *
     * @return array{items: Collection, subtotal: float, shippingTotal: float, shippingPerItem: array<int, float>, discount: float, coupon: ?Coupon, total: float}
     */
    private function resolveTotals(?CustomerAddress $address): array
    {
        $items = app(CartService::class)->items();
        $subtotal = $this->subtotal($items);

        $this->quote = $address?->location
            ? app(ShippingQuoteService::class)->quoteForCart($items, $address->location)
            : null;

        $shippingTotal = (float) ($this->quote['total'] ?? 0);

        $this->couponError = null;
        $this->couponApplied = null;
        $discount = 0.0;

        if (filled($this->couponCode)) {
            try {
                $this->couponApplied = app(CouponService::class)->validate(
                    $this->couponCode,
                    auth()->user(),
                    $items,
                    $subtotal
                );

                $discount = (float) $this->couponApplied['discount'];
            } catch (ValidationException $e) {
                $this->couponError = $e->getMessage();
            }
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'shippingTotal' => $shippingTotal,
            'shippingPerItem' => $this->quote['items'] ?? [],
            'discount' => $discount,
            'coupon' => $this->couponApplied['coupon'] ?? null,
            'total' => round($subtotal + $shippingTotal - $discount, 2),
        ];
    }

    public function refreshQuote(): void
    {
        $this->resolveTotals($this->selectedAddress());
    }

    public function placeOrder()
    {
        if (! auth()->check()) {
            session()->flash('login_intent', 'Inicia sesión para completar tu compra.');

            $this->redirect(route('login'));

            return;
        }

        $this->validate([
            'selectedAddressId' => ['required', 'integer'],
            'paymentMethod' => ['required', Rule::in(array_keys(config('payments.methods', [])))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $address = CustomerAddress::find($this->selectedAddressId);

        abort_if(! $address || $address->user_id !== auth()->id(), 403);

        $totals = $this->resolveTotals($address);

        if ($totals['items']->isEmpty()) {
            return redirect()->route('store.cart');
        }

        if (! $address->location) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No pudimos calcular el envío para tu dirección. Revísala e inténtalo otra vez.',
            ]);

            return;
        }

        if ($this->couponError) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $this->couponError]);

            return;
        }

        if ($totals['total'] <= 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'El total del pedido debe ser mayor a cero.',
            ]);

            return;
        }

        $cart = app(CartService::class);

        $data = new CheckoutData(
            user: auth()->user(),
            cart: $cart->currentCart(),
            items: $totals['items'],
            address: $address,
            subtotal: $totals['subtotal'],
            shippingTotal: $totals['shippingTotal'],
            discountTotal: $totals['discount'],
            total: $totals['total'],
            costTotal: $cart->costTotal(),
            shippingPerItem: $totals['shippingPerItem'],
            coupon: $totals['coupon'],
            notes: $this->notes ?: null,
        );

        try {
            $order = DB::transaction(function () use ($data) {
                $order = app(OrderService::class)->createFromCheckout($data);

                app(PaymentService::class)->charge($order, $this->paymentMethod);

                return $order;
            });

            $this->redirect(route('store.order.placed', ['order' => $order->order_number]));
        } catch (\Throwable $e) {
            report($e);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => config('app.debug')
                    ? 'No se pudo procesar el pedido: '.$e->getMessage()
                    : 'No se pudo procesar tu pedido. Inténtalo otra vez o contáctanos.',
            ]);
        }
    }

    public function render(CartService $cart)
    {
        if ($this->authRequired) {
            return view('livewire.store.checkout-auth');
        }

        $this->addresses = app(CustomerAddressService::class)->addressesFor(auth()->user());

        if ($this->selectedAddressId) {
            $exists = $this->addresses->firstWhere('id', $this->selectedAddressId);

            if (! $exists) {
                $this->selectedAddressId = null;
                $this->quote = null;
            }
        }

        if (! $this->selectedAddressId && $this->addresses->isNotEmpty()) {
            $this->selectedAddressId = (int) $this->addresses->first()->id;
        }

        $totals = $this->resolveTotals($this->selectedAddress());

        $paymentMethods = config('payments.methods', ['card' => 'Tarjeta']);

        return view('livewire.store.checkout', [
            'items' => $totals['items'],
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'shippingTotal' => $totals['shippingTotal'],
            'total' => $totals['total'],
            'paymentMethods' => $paymentMethods,
        ]);
    }
}
