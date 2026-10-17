# Checkout and payment UI

## Files

- `resources/views/livewire/store/checkout.blade.php` — checkout layout, gateway
  radios, payment blocks, Culqi and Mercado Pago modals.
- `resources/views/components/brevare/payment-methods.blade.php` — shared
  “Pago 100% seguro” trust block with provider logos.
- `resources/css/mercadopago-modal.css` — Apple-style glass system shared by
  `.mp-modal` (Mercado Pago) and `.pay-modal` (Culqi).
- `resources/js/mercadopago-modal.js` — Brick lifecycle, brand chip, DNI options.
- `resources/js/checkout.js` — Culqi modal lifecycle and error notifications.

## Gateway selector

- The radios bind to `gatewayChoice`, not `selectedGateway`.
- On a fresh checkout `gatewayChoice = ''`:
  - no radio is checked;
  - a dashed prompt says “Selecciona una pasarela de pago…”;
  - no provider block is rendered;
  - no “Continuar al pago” / “Continuar a Mercado Pago” button is rendered.
- Selecting a gateway calls `updatedGatewayChoice()`, which validates it, sets
  `selectedGateway`, releases a previous pending order if the intent changed,
  and syncs the payment method.
- Resuming a pending order copies `selectedGateway` into `gatewayChoice` so the
  correct gateway appears selected.
- Do not wrap the pay buttons in a separate unconditional `@if`; gate them with
  `gatewayChoice !== ''` too, otherwise a button appears with no selection.

## Provider trust blocks

`<x-brevare.payment-methods provider="culqi|mercadopago" compact />`

- Both show “Pago 100% seguro”, “Elige el medio que prefieras al pagar”, a
  “Powered by” line and method logos.
- Culqi: Visa, Mastercard, Diners, Amex, Yape, Plin.
- Mercado Pago: Visa, Mastercard, Diners, Amex.
- The Mercado Pago wordmark is a two-tone text mark (`Mercado` blue, `Pago`
  amber) to avoid an external brand asset breaking.
- The card-network SVGs are loaded from the Culqi public logo CDN; they are
  generic network assets, not Culqi-specific.

## Mercado Pago modal

Structure:

```html
<div id="mp-modal" data-amount data-payer-email class="mp-modal">
  <div class="mp-modal__backdrop" wire:click="closeMercadoPagoModal"></div>
  <section class="mp-modal__sheet">
    <span class="mp-modal__grabber"></span>
    <header class="mp-modal__head">…verified + title + close…</header>
    <div class="mp-modal__order">…pedido + total…</div>
    <p class="mp-modal__test">…solo con llave TEST…</p>
    <div id="mp-modal-error" class="mp-modal__alert"></div>
    <div class="mp-modal__form-card">
      <p class="mp-modal__form-label">Datos de la tarjeta</p>
      <div id="mp-modal-brick-loading">…spinner…</div>
      <div class="mp-modal__card-field">
        <div id="mp-modal-card-brick" wire:ignore class="mp-modal__brick"></div>
        <span id="mp-modal-brand" class="mp-modal__card-brand" hidden></span>
      </div>
    </div>
    <footer class="mp-modal__footer">…seguridad + Pagar con Culqi…</footer>
    <div id="mp-modal-result" class="mp-modal__result"></div>
  </section>
</div>
```

### Brick configuration

- `theme: 'flat'`, `hideFormTitle: true`, `formPadding: 0`.
- Variables: `baseColor #0a0a0c`, white inputs, `borderRadiusMedium 14px`,
  `borderRadiusLarge 18px`, `inputFocusedBoxShadow` blue 4px.
- `paymentMethods: { minInstallments: 1, maxInstallments: 1 }` — only
  “al contado”.
- `texts`: Número de tarjeta, Vencimiento, Código de seguridad, Nombre del
  titular, DNI del titular, Cuotas, Banco emisor.
- `initialization`: `amount` and `payer.email` (hides the Brick's email field).
- `onBinChange`: brand detection for the left card chip.
- `restrictIdentificationTypes()` removes “Otro” from the Brick's native
  `<select>` and keeps DNI, C.E, RUC; it runs on ready and via a MutationObserver.

### Card chip on the left

- The Brick's secure iframe is inside a visual field `<div>`.
- `positionBrandBadge()`:
  - finds `iframe[name="cardNumber"]` and its `parentElement`;
  - sets the parent's `padding-left` to `58px`;
  - positions `#mp-modal-brand` at `left + 8px`, vertically centered.
- The chip starts as a neutral card and swaps to Visa/Mastercard/Amex/Diners/
  Discover when `onBinChange` reports the BIN.
- A `ResizeObserver` repositions the chip when the Brick inserts its title or
  changes layout.

### Error display

- Form/SDK errors go in `#mp-modal-error` above the form.
- Payment errors from `mercadopago:result` go in the same red alert, then
  `remountBrick()` gets a fresh token.
- `mountBrick()` must not clear the error/result; only `openModal()` clears them
  on a fresh open. Otherwise the error disappears immediately after a rejection.
- `#mp-modal-error.scrollIntoView({ block: 'center' })` after showing an error.

## Culqi modal

- Uses `.pay-modal` / `.pay-modal__sheet` from `mercadopago-modal.css`; the same
  glass, typography and footer language as the MP modal.
- `#culqi-payment-modal` is rendered when `$usesProviderModal` and
  `$culqiSession !== []` **and** `! $settledOrderNumber`.
- The class is `pay-modal {{ $culqiFormOpened ? '' : 'hidden' }}`; on resume
  `culqiFormOpened` is true so the modal is visible from the server.
- `data-culqi-session="{{ json_encode($culqiSession) }}"` lets the JS mount the
  SDK from the server-rendered session on page load.
- The DNI input (`culqiIdentification`) is inside the Culqi payment block.

## Design system (Apple-like glass)

- Sheet: white glass gradient, `backdrop-filter: blur(40px) saturate(1.7)`,
  30px radius, layered shadow, 1px white border.
- Backdrop: blurred dark surface with warm/blue radial tints.
- Type: system UI (`-apple-system`, SF Pro fallbacks, Segoe UI Variable),
  tight tracking on large amounts, tabular numbers.
- Accent: Apple blue `#0a84ff`; success green `#1f9d63`; ink `#0a0a0c`.
- Motion: 0.24–0.34s ease-out; respect `prefers-reduced-motion`.
- Inputs: 44–48px min height, 12–14px radius, white background, subtle inset
  highlight, 4px blue focus ring.
- Autofill: force white background with
  `input:-webkit-autofill { -webkit-box-shadow: 0 0 0 1000px #fff inset }`.
- Do not style Brick internal classes; use the stable container IDs and
  `iframe[name="cardNumber"]` / `select` selectors.

## Accessibility

- Modal: `role="dialog"`, `aria-modal="true"`, labelled by the title.
- Errors: `role="alert"` / `aria-live="assertive"`.
- Radios: real `<input type="radio">` in a labelled group.
- Focus-visible rings on close, switch and retry controls.
- Keep the “Pago verificado” check as a visual trust cue, not the only signal.
