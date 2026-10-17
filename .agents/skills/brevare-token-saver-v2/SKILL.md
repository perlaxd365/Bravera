---
name: brevare-token-saver-v2
description: Engineering and operations guide for the Bravera/Brevare store (Laravel 13 + Livewire 3 checkout, Culqi and Mercado Pago payments, Apple-style payment modals, production deployment). Use for any checkout, payment gateway, order-resume, payment-UI, or deploy task in this repository.
---

# Bravera / Brevare

Use this skill to work on the store with minimal context and without breaking
the payment flows. Read only the reference that matches the task.

## Context map

- `app/Livewire/Store/Checkout.php` — checkout state, gateway selection,
  order creation/resume, gateway switching, Culqi modal callbacks.
- `app/Modules/Payments/` — gateways, `PaymentService`, status mapping.
  - `Gateways/Culqi/CulqiGateway.php`
  - `Gateways/MercadoPago/MercadoPagoGateway.php`
  - `Services/PaymentService.php`
- `resources/views/livewire/store/checkout.blade.php` — checkout UI,
  gateway radios, Culqi modal, Mercado Pago modal.
- `resources/views/components/brevare/payment-methods.blade.php` —
  provider trust block and method logos.
- `resources/css/mercadopago-modal.css` — shared Apple-style payment modal.
- `resources/js/mercadopago-modal.js` — Mercado Pago Card Payment Brick.
- `resources/js/checkout.js` — Culqi Checkout SDK, payment events, errors.
- `resources/views/store/layouts/app.blade.php` — static third-party SDKs.
- `docs/LANZAMIENTO.md` — production topology and deploy notes.

## Non-negotiable rules

1. **Never rely on the default gateway inside a gateway-specific flow.**
   `PaymentService::charge()` falls back to `payments.default_gateway`. Every
   Culqi call must pass `gatewayName: 'culqi'`; Mercado Pago must pass
   `gatewayName: 'mercadopago'`. Missing this sent Culqi tokens to Mercado
   Pago in production.
2. **One order = one payment intent.** `payment_intent` isolates the gateway.
   Switching gateways must cancel the pending order, release stock and restore
   the cart before creating the new attempt. Do not block the switch.
3. **No gateway is preselected on a fresh checkout.** `selectedGateway` is the
   backend/default value; `gatewayChoice` is the visible state. Empty
   `gatewayChoice` means no radio, no payment block and no pay button. Resume
   flows copy the real gateway into `gatewayChoice`.
4. **Culqi's SDK must be loaded from the layout `<head>` on `/checkout`.**
   A `@push('head')` inside the Livewire component is too late: the layout
   renders `<head>` before the component. The SDK is
   `https://js.culqi.com/checkout-js`.
5. **Culqi opens on a clean page load.** After creating the Culqi order,
   redirect to `checkout?pagar=<order>`; on mount rebuild the session, set
   `culqiFormOpened = true` and force `selectedGateway = 'culqi'`. Do not
   depend only on a Livewire event to mount the embedded SDK.
6. **Culqi charges need a valid DNI.** Send DNI in `antifraud_details`
   (`identification_type`, `identification_number`). `invalid_identification`
   means the document is missing/invalid. Test keys use `12345678` as fallback;
   live keys require the customer's 8-digit DNI.
7. **Mercado Pago uses the Payments API and the official Card Payment Brick.**
   Do not send `payment_method_id`/`issuer_id` in the gateway payload; MP
   infers them from the token. Do not collect raw card fields outside the Brick.
8. **Payment errors must survive re-renders.** Remounting the Brick to get a
   fresh token must not clear the error alert. Show payment errors above the
   form (`#mp-modal-error`) and scroll them into view.
9. **Mercado Pago is one installment, DNI/C.E/RUC only.** `maxInstallments: 1`,
   and remove the Brick's "Otro" identification option after render.
10. **Never print `.env`, access tokens, webhook secrets or customer data.**
    Verify credentials by boolean/prefix only.

## Reference routing

| Task | Read |
| --- | --- |
| Culqi/Mercado Pago charge, webhook, order resume, gateway switch | `references/payments.md` |
| Payment modal design, Brick styling, brand chip, gateway selector | `references/checkout-ui.md` |
| Tests, build, production deploy, asset verification | `references/verify-deploy.md` |
| Why today's fixes exist (incident history) | `references/incidents-2026-10-17.md` |
| Working style and token discipline | `references/working-style.md` |

Ignore the legacy nested copy at
`brevare-token-saver-v2/.agents/skills/brevare-token-saver/`; its execution
policy is preserved in `references/working-style.md`.

## Fast commands

```powershell
php artisan test tests/Feature/Payments tests/Feature/Store/CheckoutTest.php tests/Feature/Admin/RefundManagementTest.php
npm run build
php artisan view:clear; php artisan view:cache
```

Deployment and asset checks are in `references/verify-deploy.md`.

## Known failure signatures

| Symptom | Likely cause | Fix |
| --- | --- | --- |
| Culqi modal opens empty / never appears | SDK event before DOM, `@push` too late, or resume kept default MP gateway | Static SDK in layout, server-rendered visible modal, resume forces `culqi`, retry mount |
| `invalid_identification` | Missing/invalid DNI in Culqi antifraud details | Send valid `identification_type` + `identification_number` |
| Culqi charge recorded as `gateway=mercadopago` | `PaymentService::charge()` missing `gatewayName` | Pass `'culqi'` explicitly |
| MP rejected payment shows no message | Brick remount cleared the alert | Keep error above form; do not clear on remount |
| User can pay without choosing a gateway | Pay button not gated by `gatewayChoice` | Gate the payment block and buttons with `gatewayChoice !== ''` |

## Response style

Report changed files, focused checks and blockers. Do not dump credentials,
full logs or customer data.
