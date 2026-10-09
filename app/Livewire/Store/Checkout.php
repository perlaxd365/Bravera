<?php

namespace App\Livewire\Store;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Coupon;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Payment;
use App\Modules\Discounts\Services\CouponService;
use App\Modules\Location\Services\LocationService;
use App\Modules\Ordering\Data\CheckoutData;
use App\Modules\Ordering\Services\OrderService;
use App\Modules\Payments\Exceptions\CulqiApiException;
use App\Modules\Payments\Exceptions\PaymentGatewayNotConfiguredException;
use App\Modules\Payments\Gateways\Culqi\CulqiGateway;
use App\Modules\Payments\Gateways\Culqi\CulqiStatus;
use App\Modules\Payments\Services\PaymentService;
use App\Modules\Shipping\Services\ShippingQuoteService;
use App\Services\CartService;
use App\Services\CustomerAddressService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;
use Throwable;

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

    /**
     * Fase del pago para la que está la pantalla: inactivo, procesando,
     * confirmado o pendiente de confirmación.
     *
     * Existe para que el comprador no vea volver el botón de pagar mientras la
     * pasarela contesta. El cobro contra Culqi tarda y, sin esta fase, el
     * checkout se quedaba con el botón normal como si nada estuviera pasando y
     * el cliente lo volvía a pulsar, abriendo un segundo intento de cobro.
     */
    public string $paymentStage = 'idle';

    /**
     * Pedido creado para el modal de la pasarela, con su orden en Culqi.
     *
     * Es de solo lectura a propósito: el navegador recibe el identificador en
     * el evento que abre el modal, pero no puede elegir a qué pedido se le
     * cobra. El servidor siempre usa el que creó él al abrir el modal.
     */
    #[Locked]
    public ?int $culqiOrderId = null;

    #[Locked]
    public ?string $pendingOrderNumber = null;

    /**
     * Datos que el modal necesita para abrirse: orden de Culqi, importe y los
     * métodos que se habilitan. Misma razón de solo lectura que arriba.
     */
    #[Locked]
    public array $culqiSession = [];

    #[Locked]
    public bool $culqiFormOpened = false;

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

    public function mount(CartService $cart, ?string $pagar = null)
    {
        $this->authRequired = ! auth()->check();

        if ($this->authRequired) {
            session()->flash('login_intent', 'Inicia sesión para completar tu compra.');

            return;
        }

        // "Pagar ahora" desde la cuenta entra por query (?pagar=...), no por
        // parámetro de ruta. Livewire no garantiza que los pase a mount(), así
        // que se leen de la petición: si el monto llega recortado o vacío, no
        // se intenta reanudar nada.
        $pagar = is_string($pagar) && $pagar !== ''
            ? $pagar
            : request()->query('pagar');

        $pagar = is_string($pagar) ? trim($pagar) : '';

        // Reanudar un pedido pendiente tiene prioridad sobre el carrito: al
        // dejar el modal sin pagar, el carrito ya está convertido y está vacío,
        // así que sin esto el cliente se quedaría en la página de carrito sin
        // forma de pagar lo que ya dejó pedido.

        $pagar = is_string($pagar) ? trim($pagar) : '';

        /*
 * Si venimos desde "Pagar ahora" con un número de pedido,
 * intentamos recuperar exactamente ese pedido pendiente.
 */
        if ($pagar !== '' && $this->resumeModalOrder($pagar)) {
            $this->addresses = app(CustomerAddressService::class)
                ->addressesFor(auth()->user());

            return;
        }

        // Si el cliente vuelve al checkout después de cerrar Culqi, el carrito
        // ya está convertido en el pedido pendiente. Recuperamos ese pedido y
        // reabrimos la misma orden en vez de mandarlo al carrito vacío.
        if ($cart->isEmpty() && $this->resumeLatestPendingModalOrder()) {
            $this->addresses = app(CustomerAddressService::class)
                ->addressesFor(auth()->user());

            return;
        }

        if ($cart->isEmpty()) {
            return redirect()->route('store.cart');
        }

        /*
 * Si realmente no hay carrito ni una orden pendiente que recuperar,
 * entonces sí volvemos al carrito.
 */
        if ($cart->isEmpty()) {
            return redirect()->route('store.cart');
        }

        $supported = $this->supportedMethods();

        // La propiedad nace en 'card', pero puede que la pasarela activa no lo
        // admita. Dejarla en un método no soportado rompería la vista.
        if (! in_array($this->paymentMethod, $supported, true)) {
            $this->paymentMethod = $supported[0] ?? 'card';
        }

        $this->addresses = new Collection;
    }

    private function resumeLatestPendingModalOrder(): bool
    {
        $hours = (int) config('payments.pending_order_hours', 24);

        $order = Order::query()
            ->where('user_id', auth()->id())
            ->where('status', OrderStatus::PENDING)
            ->where('payment_status', PaymentStatus::PENDING)
            ->whereNotNull('gateway_order_id')
            ->where('created_at', '>=', now()->subHours(max(1, $hours)))
            ->latest('id')
            ->first();

        if ($order === null) {
            return false;
        }

        $this->culqiOrderId = $order->id;
        $this->pendingOrderNumber = $order->order_number;

        $this->culqiSession = $this->buildCulqiSession(
            $order,
            (string) $order->gateway_order_id,
            $order->payments()
                ->where('gateway', 'culqi')
                ->latest('id')
                ->value('checkout_url')
        );

        return $this->culqiSession !== [];
    }

    /**
     * Deja el checkout listo para cobrar un pedido pendiente del cliente.
     *
     * Es el camino de "Pedir ahora" en mi cuenta: el pedido y su orden en Culqi
     * ya existen, así que no se crea nada nuevo, solo se reconstruye la sesión
     * del modal sobre la misma orden. Si el pedido ya no se puede pagar (fue
     * cancelado por el comando de expiración, o ya está pagado) devuelve false y
     * el checkout sigue su curso normal.
     */
    private function resumeModalOrder(string $orderNumber): bool
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', auth()->id())
            ->first();

        if ($order === null) {
            return false;
        }

        if ($order->status !== OrderStatus::PENDING || $order->payment_status !== PaymentStatus::PENDING) {
            return false;
        }

        // Fuera de plazo ya no es una reserva viva: se deja que el comando lo
        // cancele, y el cliente vuelve al carrito en vez de ver un modal que
        // Culqi rechazaría.
        $hours = (int) config('payments.pending_order_hours', 24);

        if ($order->created_at->lt(now()->subHours(max(1, $hours)))) {
            return false;
        }

        $this->culqiOrderId = $order->id;
        $this->pendingOrderNumber = $order->order_number;

        // Sin orden en Culqi (un pedido que no llegó a abrir el modal) se crea
        // aquí; con ella, se reutiliza para no dejar órdenes huérfanas.
        $this->culqiSession = $order->gateway_order_id
            ? $this->buildCulqiSession($order, (string) $order->gateway_order_id, $order->payments()->where('gateway', 'culqi')->latest('id')->value('checkout_url'))
            : ($this->openCulqiModal($order) ?? []);

        if ($this->culqiSession === []) {
            return false;
        }

        return true;
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

    /**
     * Crea el pedido y cobra.
     *
     * $cardToken lo produce el Tokens API de Culqi en el navegador: los datos de la tarjeta
     * nunca llegan a este servidor, solo el identificador opaco. Se valida el
     * formato aquí y Culqi lo vuelve a validar al cobrar.
     *
     * Es el camino de las pasarelas que cobran desde la tienda. Las que cobran
     * dentro de su propio modal (payments.modal_gateways) no pasan por aquí:
     * su pago se resuelve en startCulqiCheckout() y sus callbacks.
     */
    public function placeOrder(?string $cardToken = null)
    {
        if (auth()->check() === false) {
            session()->flash('login_intent', 'Inicia sesión para completar tu compra.');

            $this->redirect(route('login'));

            return;
        }

        // El token solo aplica al método tarjeta y se descarta tras el intento.
        $cardToken = $this->paymentMethod === 'card' ? $cardToken : null;

        if ($this->paymentMethod === 'card' && $this->gatewayAcceptsCard()) {
            if (! is_string($cardToken) || ! str_starts_with($cardToken, 'tkn_')) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Ingresa los datos de tu tarjeta para continuar.',
                ]);

                return;
            }
        }

        $order = $this->buildOrder([
            'paymentMethod' => ['required', Rule::in($this->supportedMethods())],
        ]);

        if ($order === null) {
            return;
        }

        try {
            // El cobro va FUERA de toda transacción. Culqi no tiene
            // idempotency key: si la transacción envolviera el cobro y luego
            // revirtiera, el cliente habría pagado sin pedido registrado.
            $payment = app(PaymentService::class)->charge(
                $order,
                $this->paymentMethod,
                sourceId: $cardToken,
            );

            // Un pago asíncrono (Yape/QR) no está confirmado todavía: el
            // cliente debe completarlo en Culqi antes de seguir.
            if ($this->isPending($payment) && $payment->checkout_url) {
                $this->redirect($payment->checkout_url, navigate: false);

                return;
            }

            $this->redirect(route('store.order.placed', ['order' => $order->order_number]));
        } catch (Throwable $e) {
            report($e);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => config('app.debug')
                    ? 'No se pudo procesar el pedido: '.$e->getMessage()
                    : 'No se pudo procesar tu pedido. Inténtalo otra vez o contáctanos.',
            ]);
        }
    }

    /**
     * Prepara el pedido que se va a pagar y abre el checkout del proveedor.
     *
     * El pedido y su orden en Culqi se crean ANTES de abrir el modal porque
     * los métodos asíncronos (Yape, billetera, banca móvil, agente, Cuotéalo)
     * no se pueden pagar sin una orden previa, y el modal la necesita en
     * `settings.order`. Como el pedido queda con su precio congelado y su stock
     * reservado, un comprador que cierre el modal sin pagar deja la reserva
     * bloqueada: de eso se ocupa brevare:expire-pending-orders.
     */
    public function startCulqiCheckout(): void
    {
        if (! $this->usesProviderModal()) {
            $this->placeOrder();

            return;
        }

        // Reintento dentro de la misma sesión: el pedido y su orden ya existen,
        // así que se vuelve a abrir el modal en vez de crear un segundo pedido.
        // (El carrito ya está convertido, así que no se puede cobrar dos veces.)
        $existing = $this->payableModalOrder(announce: false);

        if ($existing !== null && $this->culqiSession !== []) {
            $this->culqiFormOpened = true;
            $this->dispatch('culqi:open', session: $this->culqiSession);

            return;
        }

        $order = $this->buildOrder();

        if ($order === null) {
            return;
        }

        $session = $this->openCulqiModal($order);

        if ($session === null) {
            return;
        }

        $this->culqiOrderId = $order->id;
        $this->culqiSession = $session;
        $this->culqiFormOpened = true;

        // A partir de aquí el pedido pendiente es el propietario del carrito.
        // Si el navegador se actualiza, la pantalla puede recuperar este pedido
        // y seguir el pago sin crear una segunda compra.
        $order->cart?->update(['status' => 'converted']);

        $this->dispatch('culqi:open', session: $session);
    }

    public function returnToCart(CartService $cart, OrderService $orders): void
    {
        $cart->restorePendingCartForEditing($orders);

        $this->redirect(route('store.cart'));
    }

    public function cancelCulqiCheckout(OrderService $orders): void
    {
        if (in_array($this->paymentStage, ['processing', 'pending', 'paid'], true)) {
            return;
        }

        $order = $this->sessionOrder();

        if ($order && $order->status === OrderStatus::PENDING && $order->payment_status === PaymentStatus::PENDING) {
            $orders->abandonPendingOrder(
                $order,
                'El comprador canceló el intento de pago en Culqi.'
            );

            $order->refresh();

            if ($order->payment_status === PaymentStatus::PAID) {
                $this->settlePayment('paid', $order);

                return;
            }
        }

        if ($order?->payment_status !== PaymentStatus::PAID) {
            $order?->cart?->update(['status' => 'active']);
        }

        $this->culqiOrderId = null;
        $this->pendingOrderNumber = null;
        $this->culqiSession = [];
        $this->culqiFormOpened = false;
        $this->paymentStage = 'idle';

        $this->dispatch('culqi:cancelled');

        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Pago cancelado. Tus productos siguen en el carrito.',
        ]);
    }

    public function culqiFormFailed(): void
    {
        if ($this->paymentStage === 'idle') {
            $this->culqiFormOpened = false;
        }
    }

    #[On('culqi:complete-card')]
    public function completeCulqiCard($payload): void
    {
        $token = is_string($payload) ? $payload : ($payload['token'] ?? '');

        if (! str_starts_with($token, 'tkn_')) {
            \Log::warning('[Culqi] Token de tarjeta inválido.');
            $this->settlePayment('idle', null, 'No recibimos los datos de tu tarjeta. Inténtalo de nuevo.');

            return;
        }

        $order = $this->payableModalOrder();

        if ($order === null) {
            \Log::warning('[Culqi] No hay pedido pagable');
            $this->settlePayment('idle');

            return;
        }

        \Log::info('[Culqi] Pedido encontrado para cobrar', ['order_id' => $order->id, 'order_number' => $order->order_number]);

        $this->paymentStage = 'processing';

        try {
            $payment = app(PaymentService::class)->charge($order, 'card', sourceId: $token);
            \Log::info('[Culqi] PaymentService::charge completado', [
                'payment_id' => $payment->id,
                'payment_status' => $payment->status->value,
                'payment_success' => $payment->status === PaymentStatus::PAID,
            ]);
        } catch (Throwable $e) {
            \Log::error('[Culqi] Excepción en charge', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            report($e);

            $this->settlePayment('idle', null, config('app.debug')
                ? 'No se pudo procesar el pago: '.$e->getMessage()
                : 'No se pudo procesar tu pago. Inténtalo otra vez.');

            return;
        }

        \Log::info('[Culqi] finishCardOrder llamado', [
            'payment_status' => $payment->status->value,
            'payment_id' => $payment->id,
        ]);
        $this->finishCardOrder($order, $payment);
    }

    #[On('culqi:complete-async')]
    public function completeCulqiAsync(string $culqiMethod): void
    {
        $order = $this->payableModalOrder();

        if ($order === null) {
            $this->settlePayment('idle');

            return;
        }

        $this->paymentStage = 'processing';

        $method = (string) config('payments.culqi.method_map.'.$culqiMethod, '');

        if ($method === '') {
            // methodValue "confirm_order": Culqi está confirmando una orden ya
            // creada, así que el pago es real aunque no esté en el catálogo.
            // Lo que no puede pasar es inventarse el pedido, y eso lo impide
            // que la orden se haya creado al abrir este mismo modal.
            $method = $culqiMethod !== '' ? $culqiMethod : 'order';

            Log::info('Método de Culqi fuera de la configuración del modal.', [
                'method' => $culqiMethod,
                'order_id' => $order->id,
            ]);
        }

        $gatewayOrderId = (string) $order->gateway_order_id;

        if ($gatewayOrderId === '') {
            $this->settlePayment('idle', null, 'No pudimos iniciar ese medio de pago. Elige otro.');

            return;
        }

        try {
            app(PaymentService::class)->recordPendingAsyncPayment(
                $order,
                $method,
                $gatewayOrderId,
                checkoutUrl: $this->culqiSession['checkoutUrl'] ?? null,
            );
        } catch (Throwable $e) {
            report($e);

            $this->settlePayment('idle', null, config('app.debug')
                ? 'No se pudo registrar el pago: '.$e->getMessage()
                : 'No pudimos registrar tu pago. Inténtalo otra vez.');

            return;
        }

        // Un método asíncrono no se confirma aquí: el pedido queda visible como
        // pendiente hasta que el webhook lo confirme. No se salta a la url de
        // pago de Culqi porque el comprador ya terminó de pagar dentro del
        // modal, y devolverlo a ella lo haría retroceder un paso.
        $this->settlePayment('pending', $order, 'Te avisamos en cuanto se confirme tu pago.');
    }

    /**
     * Cierra el intento de pago dejando la redirección en el navegador.
     *
     * El servidor no manda al comprador por su cuenta: avisa del resultado y es
     * el script del modal quien muestra la animación y luego navega, para que
     * el cierre se vea en lugar de ser un salto seco. Con 'idle' no hay nada que
     * navegar: el aviso se retira y el reintento vuelve a estar disponible.
     */
    private function settlePayment(string $kind, ?Order $order = null, ?string $message = null): void
    {
        \Log::info('[Culqi] settlePayment llamado', ['kind' => $kind, 'order' => $order?->order_number, 'message' => $message]);
        $this->paymentStage = $kind;

        if ($kind === 'idle') {
            $this->culqiFormOpened = false;
        }

        // Limpiar la sesión de Culqi si el pago fue exitoso para evitar re-abrir el modal
        // Pero mantener culqiOrderId para que payableModalOrder pueda encontrar el pedido
        // y redirigir si se intenta pagar de nuevo
        if ($kind === 'paid') {
            $this->culqiSession = [];
        }

        $url = $kind === 'idle' || $order === null
            ? null
            : route('store.order.placed', ['order' => $order->order_number]);
        \Log::info('[Culqi] settlePayment dispatching event', ['kind' => $kind, 'url' => $url]);
        $this->dispatch('culqi:settled', kind: $kind, url: $url, message: $message);

        if ($message === null) {
            return;
        }

        $this->dispatch('notify', [
            'type' => $kind === 'idle' ? 'error' : 'info',
            'message' => $message,
        ]);
    }

    /**
     * Crea la orden en Culqi y devuelve lo que el modal necesita para abrirse.
     *
     * Devuelve null (dejando el aviso en pantalla y el stock liberado) cuando
     * la orden no se pudo crear: sin ella el modal solo podría ofrecer tarjeta,
     * y el comprador vería métodos que no puede pagar.
     *
     * @return array{orderId: int, orderNumber: string, gatewayOrderId: string, amount: int, currency: string, email: ?string, methods: array<string, bool>, checkoutUrl: ?string}|null
     */
    private function openCulqiModal(Order $order): ?array
    {
        $gateway = $this->culqiGateway();

        if ($gateway === null) {
            $this->abandonModalOrder($order, 'No hay una pasarela de pago operativa.');

            return null;
        }

        try {
            $created = $gateway->createPaymentOrder(
                $order,
                (array) config('payments.culqi.order_payment_methods', [])
            );
        } catch (PaymentGatewayNotConfiguredException $e) {
            report($e);

            $this->abandonModalOrder($order, 'No pudimos conectar con la pasarela de pago. Inténtalo otra vez.');

            return null;
        } catch (CulqiApiException|RuntimeException $e) {
            report($e);

            $this->abandonModalOrder($order, $this->clientMessage($e));

            return null;
        }

        if ($created['id'] === null) {
            $this->abandonModalOrder($order, 'La pasarela no habilitó el pago. Inténtalo otra vez.');

            return null;
        }

        $order->update(['gateway_order_id' => $created['id']]);

        return $this->buildCulqiSession($order, $created['id'], $created['checkout_url']);
    }

    /**
     * Datos que el modal necesita para abrirse sobre un pedido concreto.
     *
     * Se construye aparte de la llamada a Culqi porque un pedido que ya tiene
     * `gateway_order_id` se vuelve a abrir sin crear una orden nueva: es lo que
     * permite retomar el pago de un pedido pendiente desde la cuenta del
     * cliente en vez de obligarlo a rehacer el carrito.
     *
     * @return array{orderId: int, orderNumber: string, gatewayOrderId: string, amount: int, currency: string, email: ?string, methods: array<string, bool>, checkoutUrl: ?string}
     */
    private function buildCulqiSession(Order $order, string $gatewayOrderId, ?string $checkoutUrl): array
    {
        return [
            'orderId' => $order->id,
            'orderNumber' => (string) $order->order_number,
            'gatewayOrderId' => $gatewayOrderId,
            // Culqi trabaja en céntimos enteros: 11200 = S/ 112.00.
            'amount' => (int) round((float) $order->total * 100),
            'currency' => (string) ($order->currency ?: 'PEN'),
            'email' => $order->user?->email ?? data_get($order->customer_snapshot, 'email'),
            'methods' => $this->culqiModalMethods(),
            'checkoutUrl' => $checkoutUrl,
        ];
    }

    /**
     * Valida el checkout, calcula los importes en el servidor y crea el pedido.
     *
     * Lo comparten los dos caminos de pago: el directo (placeOrder) y el que
     * abre el modal de la pasarela (startCulqiCheckout). Devuelve null, con el
     * aviso ya puesto en pantalla, cuando no se puede seguir.
     *
     * @param  array<string, array<int, mixed>>  $extraRules
     */
    private function buildOrder(array $extraRules = []): ?Order
    {
        if (auth()->check() === false) {
            session()->flash('login_intent', 'Inicia sesión para completar tu compra.');

            $this->redirect(route('login'));

            return null;
        }

        $this->validate(array_merge([
            'selectedAddressId' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], $extraRules));

        $address = CustomerAddress::find($this->selectedAddressId);

        abort_if(! $address || $address->user_id !== auth()->id(), 403);

        $totals = $this->resolveTotals($address);

        if ($totals['items']->isEmpty()) {
            $this->redirect(route('store.cart'));

            return null;
        }

        if (! $address->location) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No pudimos calcular el envío para tu dirección. Revísala e inténtalo otra vez.',
            ]);

            return null;
        }

        if ($this->couponError) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $this->couponError]);

            return null;
        }

        if ($totals['total'] <= 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'El total del pedido debe ser mayor a cero.',
            ]);

            return null;
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

        // 0. Si la pasarela no puede cobrar, no se crea el pedido: un fallo
        //    de configuración no debe dejar pedidos huérfanos. Se avisa en
        //    pantalla porque el mensaje ya está redactado para el comprador.
        try {
            app(PaymentService::class)->assertGatewayUsable();
        } catch (PaymentGatewayNotConfiguredException $e) {
            report($e);

            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);

            return null;
        }

        // 1. Pedido y reserva de stock en su propia transacción: es rápido
        //    y solo toca la base de datos local.
        return DB::transaction(fn () => app(OrderService::class)->createFromCheckout($data));
    }

    /**
     * El pedido que se creó al abrir el modal, si todavía se puede pagar.
     *
     * El identificador no lo elige el navegador: sale de una propiedad de solo
     * lectura que rellenó el servidor al abrir el modal. Aun así se comprueba
     * que el pedido sea del usuario y que siga pendiente, porque entre abrir el
     * modal y cobrar pueden pasar minutos.
     */
    private function payableModalOrder(bool $announce = true): ?Order
    {
        $order = $this->sessionOrder();

        if ($order === null) {
            if ($announce) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'La sesión del pago venció. Vuelve a intentarlo.',
                ]);
            }

            return null;
        }

        // Ya pagado, cancelado o caducado: no se cobra dos veces.
        if ($order->payment_status !== PaymentStatus::PENDING) {
            if ($announce) {
                $this->redirect(route('store.order.placed', ['order' => $order->order_number]));
            }

            return null;
        }

        return $order;
    }

    /**
     * Cierra el flujo de tarjeta: al pedido confirmado se lo lleva la página de
     * gracias y en cualquier otro caso se avisa en la propia pantalla.
     *
     * No se salta a la url de pago de Culqi: la tarjeta se cobra en el momento
     * y un pago pendiente aquí solo significa que falta la confirmación.
     */
    private function finishCardOrder(Order $order, Payment $payment): void
    {
        Log::info('[Culqi] finishCardOrder', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payment_id' => $payment->id,
            'payment_status' => $payment->status->value,
        ]);

        if ($payment->status === PaymentStatus::PENDING) {
            $this->settlePayment(
                'pending',
                $order,
                'Estamos verificando tu pago. En unos minutos te confirmamos.'
            );

            return;
        }

        if ($payment->status === PaymentStatus::FAILED) {
            $this->settlePayment(
                'idle',
                null,
                'No pudimos completar el pago. Prueba con otra tarjeta o con otro medio de pago.'
            );

            return;
        }

        if ($payment->status !== PaymentStatus::PAID) {
            Log::warning('[Culqi] Estado de pago inesperado', [
                'payment_id' => $payment->id,
                'status' => $payment->status->value,
            ]);

            $this->settlePayment(
                'idle',
                null,
                'El pago no pudo ser confirmado.'
            );

            return;
        }

        Log::info('[Culqi] PAGO PAID - CONVIRTIENDO CARRITO', [
            'order_number' => $order->order_number,
        ]);

        $this->settlePayment('paid', $order);
    }

    /**
     * Cancela el pedido que se acaba de crear y avisa al comprador.
     *
     * El pedido ya reserves stock antes de abrir el modal, así que si la orden
     * no llega a crearse hay que devolver el stock: dejarlo reservado sería
     * una venta fantasma que bloquea unidades del inventario.
     */
    private function abandonModalOrder(Order $order, string $message): void
    {
        app(OrderService::class)->abandonPendingOrder($order, $message);

        $this->dispatch('notify', ['type' => 'error', 'message' => $message]);
    }

    /**
     * Traduce un fallo de Culqi a algo que el comprador pueda corregir.
     *
     * El user_message de Culqi a veces solo dice "contactate con soporte", que
     * no le sirve de nada: el problema no es suyo. Cuando es así, la excepción
     * ya trae el motivo en su propio mensaje.
     */
    private function clientMessage(Throwable $e): string
    {
        $userMessage = $e instanceof CulqiApiException
            ? CulqiStatus::userMessage($e->payload)
            : null;

        return $userMessage ?? $e->getMessage();
    }

    /**
     * Métodos que se habilitan en el modal, en el vocabulario de Culqi.
     *
     * Se mandan todos los declarados en config: Culqi oculta los que la cuenta
     * no tenga, así que no hace falta conocer la habilitación real y el
     * comprador ve todo lo que Culqi tiene para él.
     *
     * @return array<string, bool>
     */
    private function culqiModalMethods(): array
    {
        $methods = [];

        foreach ((array) config('payments.culqi.modal_methods', []) as $method) {
            $methods[(string) $method] = true;
        }

        return $methods;
    }

    private function isPending(Payment $payment): bool
    {
        return $payment->status === PaymentStatus::PENDING;
    }

    /**
     * ¿La pasarela activa cobra dentro de su propio modal?
     */
    private function usesProviderModal(): bool
    {
        $gateway = config('payments.default_gateway');

        return is_string($gateway)
            && in_array($gateway, (array) config('payments.modal_gateways', []), true);
    }

    private function culqiGateway(): ?CulqiGateway
    {
        $class = config('payments.gateways.'.config('payments.default_gateway'));

        return is_string($class) && is_a($class, CulqiGateway::class, true)
            ? app($class)
            : null;
    }

    /**
     * ¿La pasarela activa exige que el navegador tokenice la tarjeta?
     */
    private function gatewayAcceptsCard(): bool
    {
        $gateway = config('payments.default_gateway');

        return is_string($gateway)
            && in_array($gateway, (array) config('payments.card_token_gateways', []), true);
    }

    /**
     * Métodos que la pasarela activa admite de verdad.
     *
     * Sin esta restricción el checkout ofrece, por ejemplo, "Transferencia
     * bancaria" con Culqi, que solo admite tarjeta y Yape. El pedido se
     * crearía y solo podría fallar al cobrar, reservando stock en el camino.
     *
     * @return list<string>
     */
    private function supportedMethods(): array
    {
        $catalog = array_keys((array) config('payments.methods', []));

        $gateway = config('payments.default_gateway');

        if (! is_string($gateway) || $gateway === '') {
            return $catalog;
        }

        $supported = (array) config('payments.gateway_methods.'.$gateway, []);

        // Una pasarela sin métodos declarados no restringe el catálogo: se
        // comporta como antes en vez de dejar la tienda sin formas de cobrar.
        if ($supported === []) {
            return $catalog;
        }

        return array_values(array_intersect($catalog, $supported));
    }

    /**
     * Catálogo de métodos etiquetado para la vista, ya recortado a los que la
     * pasarela admite.
     *
     * @return array<string, string>
     */
    private function availablePaymentMethods(): array
    {
        return array_intersect_key(
            (array) config('payments.methods', []),
            array_flip($this->supportedMethods()),
        );
    }

    public function render(CartService $cart)
    {
        if ($this->authRequired) {
            return view('livewire.store.checkout-auth');
        }

        $this->addresses = app(CustomerAddressService::class)
            ->addressesFor(auth()->user());

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

        $usesModal = $this->usesProviderModal();

        $paymentMethods = $usesModal
            ? []
            : $this->availablePaymentMethods();

        $order = $usesModal
            ? $this->sessionOrder()
            : null;

        if ($order !== null) {
            Log::info('[Checkout] ORDEN RECUPERADA EN RENDER', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'payment_status' => $order->payment_status->value,
                'items_count' => $order->items()->count(),
                'subtotal' => $order->subtotal,
                'shipping_total' => $order->shipping_total,
                'total' => $order->total,
            ]);
        }

        $awaitingPayment = $order !== null
            && $order->status === OrderStatus::PENDING
            && $order->payment_status === PaymentStatus::PENDING;

        if ($awaitingPayment) {
            // IMPORTANTE:
            // El carrito ya está vacío porque fue convertido en pedido.
            // El resumen debe salir de la orden pendiente.
            $totals = $this->totalsFromOrder($order);
        } else {
            $totals = $this->resolveTotals(
                $this->selectedAddress()
            );
        }

        $summaryItems = $totals['items']->map(fn ($item) => [
                'quantity' => (int) $item->quantity,
                'name' => $item->variant?->product?->name
                    ?? $item->product_name
                    ?? 'Producto',
                'subtotal' => round(
                    (float) $item->unit_price * (int) $item->quantity,
                    2
                ),
                'regularSubtotal' => round(max(
                    (float) ($item->variant?->compare_price ?? $item->unit_price),
                    (float) $item->unit_price
                ) * (int) $item->quantity, 2),
                'discountPercent' => (float) ($item->variant?->discount_percent ?: (1 - ((float) $item->unit_price / max(0.01, (float) ($item->variant?->compare_price ?? $item->unit_price)))) * 100),
            ]);
        $productDiscount = round($summaryItems->sum(fn ($item) => max(0, $item['regularSubtotal'] - $item['subtotal'])), 2);

        return view('livewire.store.checkout', [
            'summaryItems' => $summaryItems,
            'productDiscount' => $productDiscount,
            'regularSubtotal' => round($totals['subtotal'] + $productDiscount, 2),

            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'shippingTotal' => $totals['shippingTotal'],
            'total' => $totals['total'],

            'paymentMethods' => $paymentMethods,
            'usesProviderModal' => $usesModal,
            'culqiPublicKey' => $this->culqiPublicKey(),

            'awaitingPayment' => $awaitingPayment,

            'pendingOrderNumber' => $awaitingPayment
                ? $order->order_number
                : null,

            'settledOrderNumber' => $order !== null && ! $awaitingPayment
                ? $order->order_number
                : null,
        ]);
    }

    /**
     * El pedido que se creó al abrir el modal, exista o no siga pagable.
     *
     * El identificador no lo elige el navegador: sale de una propiedad de solo
     * lectura que rellenó el servidor al abrir el modal. Aun así se comprueba
     * que sea del usuario.
     */
    private function sessionOrder(): ?Order
    {
        $order = null;

        if ($this->culqiOrderId) {
            $order = Order::find($this->culqiOrderId);
        }

        if ($order === null && $this->pendingOrderNumber) {
            $order = Order::query()
                ->where('order_number', $this->pendingOrderNumber)
                ->where('user_id', auth()->id())
                ->first();
        }

        if ($order === null) {
            return null;
        }

        if ($order->user_id !== auth()->id()) {
            return null;
        }

        return $order;
    }

    /**
     * Importes del pedido ya creado, leídos de lo que quedó congelado al
     * crearlo. El carrito está convertido, así que no sirven para reconstruirlos.
     *
     * @return array{items: Collection, subtotal: float, shippingTotal: float, discount: float, total: float}
     */
    private function totalsFromOrder(Order $order): array
    {
        return [
            'items' => $order->items()->with('variant.product')->get(),
            'subtotal' => (float) $order->subtotal,
            'shippingTotal' => (float) $order->shipping_total,
            'discount' => (float) $order->discount_total,
            'total' => (float) $order->total,
        ];
    }

    /**
     * Clave pública de Culqi para abrir su checkout en el navegador.
     *
     * Es pública por diseño: solo permite tokenizar, nunca cobrar. La clave
     * secreta se queda en el servidor y no debe salir de aquí.
     */
    private function culqiPublicKey(): ?string
    {
        if (! $this->usesProviderModal() || $this->culqiGateway() === null) {
            return null;
        }

        $key = config('payments.culqi.public_key');

        return is_string($key) && trim($key) !== '' ? $key : null;
    }

    public function completeCulqiYape(string $token): void
    {
        if (! str_starts_with($token, 'ype_')) {
            Log::warning('[Culqi] Token Yape inválido.');

            $this->settlePayment(
                'idle',
                null,
                'No recibimos un token válido de Yape.'
            );

            return;
        }

        $order = $this->payableModalOrder();

        if ($order === null) {
            Log::warning('[Culqi] No hay pedido pagable para Yape.');

            $this->settlePayment('idle');

            return;
        }

        $this->paymentStage = 'processing';

        try {
            $payment = app(PaymentService::class)->charge(
                $order,
                'yape',
                sourceId: $token,
            );

            Log::info('[Culqi] Cargo Yape completado', [
                'payment_id' => $payment->id,
                'status' => $payment->status->value,
                'gateway_transaction_id' => $payment->gateway_transaction_id,
            ]);

            if ($payment->status === PaymentStatus::PAID) {
                $this->finishCardOrder($order, $payment);

                return;
            }

            if ($payment->status === PaymentStatus::PENDING) {
                $this->settlePayment(
                    'pending',
                    route('store.order.confirmed', [
                        'order' => $order->order_number,
                    ]),
                    'Te avisamos en cuanto se confirme tu pago.'
                );

                return;
            }

            $this->settlePayment(
                'idle',
                null,
                'No pudimos procesar el pago con Yape.'
            );
        } catch (Throwable $e) {
            Log::error('[Culqi] Error procesando Yape', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            report($e);

            $this->settlePayment(
                'idle',
                null,
                config('app.debug')
                    ? 'No se pudo procesar Yape: '.$e->getMessage()
                    : 'No se pudo procesar tu pago con Yape. Inténtalo nuevamente.'
            );
        }
    }
}
