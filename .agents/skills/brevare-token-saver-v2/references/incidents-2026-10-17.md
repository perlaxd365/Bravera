# Incident log — 2026-10-17

Chronology of the payment/checkout work. Use it to avoid reintroducing a fixed
bug.

## 1. Mercado Pago card form looked unprofessional

**Symptom:** Custom CardForm secure iframes looked misaligned; card digits had
odd typography; no brand detection.

**Cause:** The legacy `mp.cardForm()` secure iframes cannot be styled from the
parent page.

**Fix:** Replaced with the official **Card Payment Brick** (`cardPayment`):
professional inputs, official brand flags, token lifecycle, Amex handling.
The Brick uses the Payments API already used by the backend. The payload omits
`payment_method_id`/`issuer_id` (MP infers them from the token); sending them
caused `Invalid payment_method_id`.

**Lesson:** Never style or read PCI secure iframes; use the official Brick.

## 2. Gateway switching was blocked

**Symptom:** “Continúa el pago de este pedido con la pasarela que seleccionaste
al iniciarlo.” The user could not switch to Culqi after starting Mercado Pago.

**Cause:** A guard in `placeOrder()` blocked any pending order whose
`payment_intent` differed from the selected gateway.

**Fix:** `releaseOrderForGatewaySwitch()` cancels the pending order, releases
stock, restores the cart and clears the modal/order state. The gateway switch is
now a first-class flow. The radios are no longer disabled while awaiting
payment.

**Lesson:** A pending intent must be released, not locked.

## 3. Culqi modal opened empty or never appeared

**Symptom:** The Culqi modal showed an empty white strip; sometimes the page
just refreshed.

**Causes (found in order):**

1. The modal depended on the `culqi:open` Livewire event arriving after the DOM
   insert; if it arrived before, the container did not exist.
2. The SDK preload used `@push('head')` from the Livewire component. Blade
   renders the layout `<head>` before the component slot, so the script never
   reached the page.
3. After creating the Culqi order, the flow redirected with `?pagar=` but
   `mount()` left `selectedGateway` as the default **mercadopago**. The Culqi
   modal condition (`usesProviderModal`) was false, so it did not render.

**Fix:**

- Load `https://js.culqi.com/checkout-js` directly in
  `store/layouts/app.blade.php` on the checkout route.
- `startCulqiCheckout()` redirects to `checkout?pagar=<order>` after creating
  the order.
- `resumeModalOrder()` sets `selectedGateway = 'culqi'`, rebuilds the session
  and sets `culqiFormOpened = true`.
- The modal is server-rendered visible with `data-culqi-session`; JS mounts on
  page load and retries if the DOM is not ready.
- A watchdog hides the modal and shows an explicit message if no iframe mounts.

**Lesson:** For third-party SDKs, render the container and session from the
server on a clean page load; do not rely on an AJAX event + DOM timing.

## 4. `invalid_identification` on Culqi charges

**Symptom:** “No pudimos completar el pago. Prueba con otra tarjeta…”.

**Cause:** Culqi rejected the charge with `invalid_identification`; the
antifraud payload had no `identification_type`/`identification_number`.

**Fix:**

- `culqiIdentificationNumber()` validates 8 digits; test keys fall back to
  `12345678`, live keys require the customer's DNI.
- `startCulqiCheckout()` stores the DNI in `customer_snapshot`.
- `CulqiGateway::antifraudDetails()` sends the DNI plus documented
  `address_city`/`phone` fields.
- `client.identification` is passed to the Culqi Checkout SDK.
- A DNI field was added to the Culqi payment block.

**Lesson:** `invalid_identification` is about the document, not the card.

## 5. Culqi charges were sent to Mercado Pago

**Symptom:** Every Culqi card/Yape attempt failed; the `payments` table recorded
`gateway = mercadopago` for Culqi tokens.

**Cause:** `completeCulqiCard()` and `completeCulqiYape()` called
`PaymentService::charge()` without `gatewayName`. `PaymentService` falls back to
`payments.default_gateway`, which is **mercadopago** in production.

**Fix:** Pass `gatewayName: 'culqi'` in both paths. Added a regression test with
`default_gateway = mercadopago` asserting the charge goes to
`api.culqi.com/v2/charges` and the payment gateway is `culqi`.

**Lesson:** Gateway-specific flows must always pass the gateway explicitly.

## 6. Mercado Pago rejection showed no error

**Symptom:** With the test name `OTHE` (induced rejection), no message appeared.

**Cause:** `mercadopago:result` set the error, then `remountBrick()` called
`mountBrick()`, which cleared `setError()`/`setResult()` at the start.

**Fix:** Payment errors go to `#mp-modal-error` above the form; `mountBrick()`
no longer clears the error/result; only `openModal()` clears them on a fresh
open. The alert scrolls into view. Culqi `idle` errors now also show a
notification.

**Lesson:** A remount for a fresh token must preserve the user-facing error.

## 7. Gateway preselection and missing Mercado Pago trust block

**Symptom:** A gateway was auto-selected; Mercado Pago lacked the “Pago 100%
seguro” block shown for Culqi.

**Fix:**

- `gatewayChoice` starts empty; radios bind to it; no block/button until the
  user selects a gateway.
- `payment-methods` accepts `provider="mercadopago"` and renders the trust block
  with Visa, Mastercard, Diners and Amex.
- Resuming a pending order copies its gateway into `gatewayChoice`.

**Lesson:** Separate backend default (`selectedGateway`) from visible choice
(`gatewayChoice`).

## 8. Mercado Pago installments and “Otro”

**Symptom:** 3x–12x installments and an “Otro” document option appeared.

**Fix:** `maxInstallments: 1`; `restrictIdentificationTypes()` removes the
Brick's “Otro” option (DNI, C.E, RUC remain). Added native select styling and
an autofill override to keep the name field white.

## 9. Design system

The payment modals now share an Apple-like glass system in
`resources/css/mercadopago-modal.css`:

- translucent sheet, blurred backdrop, soft layered shadow;
- system typography, large tabular totals;
- blue focus rings, green verified chip;
- compact form label, black pay button, quiet footer;
- Mercado Pago card chip on the left of the card-number field;
- same theme for Culqi (`pay-modal` classes).

## Final verification state

- Focused suites: Payments + Checkout + Refund.
- Last run: **151 tests / 548 assertions** passing.
- Production assets: manifest hashes, both build directories, HTTP 200.
- Mercado Pago remains in TEST mode (`TEST-`/`pk_test_`); real money requires
  production credentials.
