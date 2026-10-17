# Payments architecture

## Core services and models

- `App\Modules\Payments\Services\PaymentService` — creates a `Payment`, resolves
  the gateway and captures the charge.
  - `charge(Order $order, string $method = 'card', ?string $gatewayName = null, ?string $sourceId = null, ?array $gatewayContext = null)`
  - When `$gatewayName` is `null`, it falls back to
    `config('payments.default_gateway')`. **Never omit it in a
    gateway-specific path.**
- `App\Modules\Payments\Gateways\Culqi\CulqiGateway` — Culqi orders, card and
  Yape charges, refunds, status mapping, antifraud details.
- `App\Modules\Payments\Gateways\MercadoPago\MercadoPagoGateway` — Payments API
  charges, refunds, status mapping.
- `App\Models\Payment` — one row per attempt (`gateway`, `method`, `source_id`,
  `status`, `raw_response`, `gateway_transaction_id`).
- `App\Models\Order` — `payment_intent` stores the gateway chosen for the order;
  `customer_snapshot` can carry `identification_type`/`identification_number`.

## Order lifecycle

1. Checkout validates address, totals and cart.
2. `OrderService::createFromCheckout($data, $gateway)` creates the order,
   reserves stock and converts the cart.
3. The order is `PENDING`/`PENDING` while payment is in progress.
4. Gateway callback/webhook moves it to `CONFIRMED`/`PAID`, or
   `CANCELLED`/`EXPIRED`/`FAILED`.
5. `brevare:expire-pending-orders` releases abandoned reservations.

`Checkout` keeps `$culqiOrderId` and `$pendingOrderNumber`; `sessionOrder()`
must always verify that the order belongs to `auth()->id()`.

## Gateway selection and switching

- `selectedGateway` — backend gateway used by `placeOrder`/charges.
- `gatewayChoice` — visible radio state. On a fresh checkout it is empty, so no
  gateway is preselected and no pay button is rendered.
- `updatedGatewayChoice($value)` validates against `availableGatewayNames()`,
  sets `selectedGateway`, calls `releaseOrderForGatewaySwitch()` and syncs the
  payment method.
- `releaseOrderForGatewaySwitch()`:
  - no-op if there is no order or the intent already matches;
  - abandons a payable order (`OrderService::abandonPendingOrder`), releasing
    stock;
  - reactivates the cart;
  - clears `showMercadoPagoModal`, `culqiOrderId`, `pendingOrderNumber`,
    `culqiSession`, `culqiFormOpened`, `paymentStage`;
  - forgets `mercadopago_pending_order`;
  - dispatches `culqi:cancelled` and `mercadopago:modal-closed`.
- Do not restore the old hard block that forced the first gateway. Switching is
  a normal flow.

## Resuming a pending order

- `mount()` reads `?pagar=<order_number>` (and the `mercadopago_pending_order`
  session key when the cart is empty).
- `resumeModalOrder()` / `resumeLatestPendingModalOrder()` set
  `culqiOrderId`, `pendingOrderNumber` and reconstruct the gateway:
  - `mercadopago` → `selectedGateway = 'mercadopago'`;
  - `izipay` → `selectedGateway = 'izipay'`;
  - `culqi` or null intent → `selectedGateway = 'culqi'` and rebuild
    `culqiSession` from `gateway_order_id`.
- After a successful resume, `mount()` copies `selectedGateway` into
  `gatewayChoice`.
- For Culqi, the resumed session must set `culqiFormOpened = true`; the modal
  is rendered visible from the server and mounted on page load.

## Culqi flow

### Create the order and open the modal

`startCulqiCheckout()`:

1. `releaseOrderForGatewaySwitch()`.
2. If not a provider-modal gateway, delegate to `placeOrder()`.
3. Resolve `culqiIdentificationNumber()`; if empty, notify
   “Ingresa un DNI válido de 8 dígitos…” and stop.
4. Reuse an existing payable order/session if present; refresh the order's
   `customer_snapshot` with the DNI and rebuild the session.
5. Otherwise `buildOrder()`, then store `identification_type = DNI` and
   `identification_number` in `customer_snapshot`.
6. `openCulqiModal($order)` calls `CulqiGateway::createPaymentOrder()` and
   builds the session.
7. Mark the cart as converted, set `culqiFormOpened = true`, dispatch
   `culqi:open` and **redirect** to `route('checkout', ['pagar' => $order->order_number])`.

The redirect is intentional: the SDK mounts on a clean page load, not in the
middle of a Livewire DOM update.

### Session shape

`buildCulqiSession()` returns:

```php
[
    'orderId' => int,
    'orderNumber' => string,
    'gatewayOrderId' => string,
    'amount' => int,          // cents
    'currency' => 'PEN',
    'email' => ?string,
    'identification' => string, // DNI
    'methods' => ['tarjeta' => true, 'yape' => true, ...],
    'checkoutUrl' => ?string,
]
```

### SDK loading and mounting

- `resources/views/store/layouts/app.blade.php` loads
  `<script src="https://js.culqi.com/checkout-js" async>` only on the
  `checkout` route.
- `resources/js/checkout.js`:
  - `loadCulqi()` waits for `window.CulqiCheckout` or the existing script.
  - `initCheckout()` registers `culqi:open`, `culqi:cancelled`,
    `culqi:settled` and request-failure handling.
  - `mountCulqiModal()` retries until `#culqi-payment-modal` and
    `#culqi-container` exist, shows the modal, destroys a previous instance,
    and calls `openModal()`.
  - `openModal()` creates `new CulqiCheckout(publicKey, settings)` with
    `settings.order`, `client.email`, `client.identification`, methods and
    appearance; calls `checkout.open()`; sizes `#culqi-container` and its
    iframe; and watches for a missing iframe after 4 seconds.
  - A 9-second watchdog hides the modal and notifies if no iframe mounts.

### Charging Culqi

- Card: `#[On('culqi:complete-card')] completeCulqiCard($payload)` validates a
  `tkn_` token and calls:

```php
app(PaymentService::class)->charge(
    $order,
    'card',
    gatewayName: 'culqi',
    sourceId: $token,
);
```

- Yape: `completeCulqiYape()` calls `charge($order, 'yape', gatewayName: 'culqi', sourceId: $token)`.
- The explicit `gatewayName` is mandatory. Omitting it sent the Culqi token to
  the default Mercado Pago gateway and produced confusing failures.
- `CulqiGateway::antifraudDetails()` sends `first_name`, `last_name`,
  `address`, `address_city`, `country_code`, `phone`,
  `identification_type`, `identification_number`.
- Test keys fall back to DNI `12345678` when the customer leaves the field
  empty or enters an invalid one. Live keys require 8 digits.

### Culqi errors

- `invalid_identification` → missing/invalid document. Check the antifraud
  fields and the DNI field.
- `CulqiApiException` messages are mapped by `CulqiGateway::failure()`.
- `settlePayment('idle', null, $message)` dispatches `culqi:settled`; the JS
  `handleCulqiSettled()` shows `payload.message` as a notification for `idle`.

## Mercado Pago flow

### Card Payment Brick

- `placeOrder()` with `selectedGateway = 'mercadopago'` creates/reuses the order,
  marks the cart converted, sets `showMercadoPagoModal = true` and dispatches
  `mercadopago:modal`.
- `resources/views/livewire/store/checkout.blade.php` renders the modal with
  `data-amount`, `data-payer-email` and `#mp-modal-card-brick` (`wire:ignore`).
- `resources/js/mercadopago-modal.js` loads Mercado Pago.js, creates the
  `cardPayment` Brick with `initialization.amount` + `payer.email`,
  `customization.visual` (Apple-style variables), `maxInstallments: 1`,
  `hideFormTitle: true`, and callbacks `onReady`, `onError`, `onBinChange`,
  `onSubmit`.
- `onSubmit` calls the Livewire method `payMercadoPagoBrick(formData)`.
- `payMercadoPagoBrick()` extracts `token` and
  `payer.identification.number` and calls `processMercadoPagoPayment()`.

### Charge rules

- Backend uses `POST https://api.mercadopago.com/v1/payments`.
- Do **not** send `payment_method_id` or `issuer_id`; MP infers them from the
  token. Sending them caused `Invalid payment_method_id` for Visa/Amex.
- Amount and identity come from the local order, never the browser.
- The Payments API token is `TEST-...` for Checkout API/Bricks; do not switch
  to Orders API.
- `processMercadoPagoPayment()` maps paid/pending/error and returns a message.

### Modal behavior

- The SDK is loaded at runtime and the modal mounts on `mercadopago:modal`.
- Payment errors are shown in `#mp-modal-error` (above the form) and must not be
  cleared when the Brick is remounted for a fresh token.
- The Brick's brand chip is rendered to the left inside the card-number field:
  the field's parent gets `padding-left: 58px`, and a small card SVG is
  positioned from the measured box. Brand detection uses `onBinChange`
  (Visa/Mastercard/Amex/Diners/Discover).
- `restrictIdentificationTypes()` removes the Brick's “Otro” option and leaves
  DNI, C.E and RUC.

## Webhooks

- Culqi and Mercado Pago have webhook controllers under
  `app/Http/Controllers/Webhooks` (verify signatures/secrets; never trust the
  payload without the gateway lookup).
- A webhook can confirm a payment while the HTTP charge response is still in
  flight; `PaymentService` locks the order/payment and never downgrades a paid
  order.

## Regression tests to keep

- `tests/Feature/Store/CheckoutTest.php`
  - `test_el_modal_cobra_con_culqi_aunque_el_default_sea_mercadopago`
  - `test_reanudar_un_pedido_de_culqi_selecciona_culqi_aunque_el_default_sea_mercadopago`
  - `test_el_modal_de_culqi_se_abre_con_la_orden_ya_creada`
  - `test_mercadopago_solo_se_muestra_al_elegirlo_y_con_sus_medios`
- `tests/Feature/Payments/CulqiGatewayTest.php`
  - `test_envia_el_dni_valido_en_los_detalles_antifraude`
