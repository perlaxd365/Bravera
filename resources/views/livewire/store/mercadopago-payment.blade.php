@push('scripts')
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <script>
        const secureFieldHosts = [
            { id: 'form-checkout__cardNumber', labelId: 'card-number-label' },
            { id: 'form-checkout__expirationDate', labelId: 'expiration-label' },
            { id: 'form-checkout__securityCode', labelId: 'security-code-label' },
        ];

        function showCheckoutInitError(details) {
            const region = document.getElementById('checkout-init-error');

            if (!region) {
                return;
            }

            region.hidden = false;
            region.textContent = details || 'No pudimos cargar los campos seguros de Mercado Pago.';
            console.error('[Checkout API] no se pudo inicializar el formulario:', details);
        }

        function showPaymentResult(message, isError = false) {
            const region = document.getElementById('mp-checkout-result');

            if (!region) {
                return;
            }

            region.textContent = message;
            region.hidden = !message;
            region.classList.toggle('is-error', isError);
        }

        function setPaymentButtonBusy(isBusy) {
            const button = document.getElementById('form-checkout__submit');

            if (!button) {
                return;
            }

            button.disabled = isBusy;
            button.classList.toggle('is-busy', isBusy);
            button.textContent = isBusy
                ? 'Procesando...'
                : (button.dataset.defaultLabel || 'Pagar con Mercado Pago');
        }

        async function loadMercadoPagoPublicKey() {
            let response;

            try {
                response = await fetch('/pagos/mercadopago/configuracion', {
                    cache: 'no-store',
                    headers: { Accept: 'application/json' },
                });
            } catch (error) {
                throw new Error('No pudimos acceder a la configuración de Mercado Pago. Recarga la página e inténtalo de nuevo.');
            }

            const payload = await response.json().catch(() => ({}));

            if (!response.ok || !payload.publicKey) {
                throw new Error(payload.error || 'MP_PUBLIC_KEY no está configurada en el servidor.');
            }

            return payload.publicKey;
        }

        function assertSecureFieldsMounted() {
            const failures = [];

            secureFieldHosts.forEach(({ id, labelId }) => {
                const host = document.getElementById(id);
                const label = document.getElementById(labelId);
                const frames = host ? host.querySelectorAll('iframe') : [];
                const rect = host ? host.getBoundingClientRect() : { width: 0, height: 0 };

                if (!host) {
                    failures.push(id + ': contenedor ausente');
                } else if (frames.length !== 1) {
                    failures.push(id + ': iframe no montado exactamente una vez');
                }

                if (!label || label.getClientRects().length === 0) {
                    failures.push(id + ': etiqueta visible ausente');
                }

                if (!rect.width || !rect.height) {
                    failures.push(id + ': contenedor sin dimensiones');
                }

                if (host && getComputedStyle(host).pointerEvents === 'none') {
                    failures.push(id + ': contenedor no interactivo');
                }

                if (frames[0] && getComputedStyle(frames[0]).pointerEvents === 'none') {
                    failures.push(id + ': iframe no interactivo');
                }
            });

            if (failures.length) {
                showCheckoutInitError('No pudimos montar los campos seguros: ' + failures.join('; '));
                return false;
            }

            return true;
        }

        async function mountMercadoPagoForm() {
            const root = document.getElementById('mp-checkout-root');
            const form = document.getElementById('form-checkout');

            if (!root || !form) {
                return;
            }

            if (typeof MercadoPago === 'undefined') {
                showCheckoutInitError('El SDK de Mercado Pago no se cargó. Revisa tu conexión o desactiva el bloqueador de anuncios.');
                return;
            }

            let publicKey;

            try {
                publicKey = await loadMercadoPagoPublicKey();
            } catch (error) {
                showCheckoutInitError(error.message);
                return;
            }

            const mp = new MercadoPago(publicKey);
            const cardForm = mp.cardForm({
                amount: root.dataset.amount,
                iframe: true,
                form: {
                    id: 'form-checkout',
                    cardNumber: { id: 'form-checkout__cardNumber' },
                    expirationDate: { id: 'form-checkout__expirationDate' },
                    securityCode: { id: 'form-checkout__securityCode' },
                    cardholderName: { id: 'form-checkout__cardholderName' },
                    issuer: { id: 'form-checkout__issuer' },
                    installments: { id: 'form-checkout__installments' },
                    identificationType: { id: 'form-checkout__identificationType' },
                    identificationNumber: { id: 'form-checkout__identificationNumber' },
                },
                callbacks: {
                    onFormMounted: (error) => {
                        if (error) {
                            showCheckoutInitError(error);
                            return;
                        }

                        requestAnimationFrame(assertSecureFieldsMounted);
                    },
                    onSubmit: async (event) => {
                        event.preventDefault();

                        const data = cardForm.getCardFormData();
                        const component = window.Livewire?.find?.(root.getAttribute('wire:id'));

                        if (!component) {
                            showCheckoutInitError('No pudimos conectar el formulario con el checkout. Recarga la página.');
                            return;
                        }

                        setPaymentButtonBusy(true);
                        showPaymentResult('Procesando tu pago de forma segura...');

                        try {
                            await component.call(
                                'pay',
                                data.token,
                                data.paymentMethodId,
                                data.identificationNumber ?? '',
                                data.issuerId ?? null,
                            );
                        } catch (error) {
                            console.error('[Checkout API] fallo al enviar el pago:', error);
                            setPaymentButtonBusy(false);
                            showPaymentResult('No pudimos procesar el pago. Revisa tu conexión e inténtalo de nuevo.', true);
                        }
                    },
                },
            });

            window.setTimeout(() => {
                if (!document.querySelector('#form-checkout__expirationDate iframe')) {
                    showCheckoutInitError('El campo de vencimiento no se montó. Revisa tu conexión o el bloqueador de anuncios.');
                }
            }, 8000);
        }

        window.addEventListener('mercadopago:result', (event) => {
            const payload = event?.detail ?? event ?? {};
            const root = document.getElementById('mp-checkout-root');

            if (payload.status === 'paid' || payload.status === 'pending') {
                setPaymentButtonBusy(false);
                window.location.assign(payload.url || root?.dataset.successUrl || '/mi-cuenta/pedidos');
                return;
            }

            setPaymentButtonBusy(false);
            showPaymentResult(payload.message || 'No pudimos procesar el pago. Inténtalo de nuevo.', true);
        });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', mountMercadoPagoForm, { once: true });
        } else {
            mountMercadoPagoForm();
        }
    </script>
@endpush

<div id="mp-checkout-root"
    data-amount="{{ number_format((float) $order->total, 2, '.', '') }}"
    data-success-url="{{ route('store.order.placed', ['order' => $order->order_number]) }}"
    data-order-number="{{ $order->order_number }}"
    class="mp-checkout">
    <div class="mp-ambient" aria-hidden="true">
        <span class="mp-orb mp-orb--sun"></span>
        <span class="mp-orb mp-orb--coral"></span>
    </div>

    <div class="mp-shell">
        <div class="mp-topbar">
            <a href="{{ route('checkout') }}" class="mp-back">
                <flux:icon name="arrow-left" class="size-4" />
                {{ app()->getLocale() === 'en' ? 'Back to checkout' : 'Volver al checkout' }}
            </a>
            <span class="mp-secure-pill">
                <span class="mp-live-dot" aria-hidden="true"></span>
                {{ app()->getLocale() === 'en' ? 'Secure payment' : 'Pago seguro' }}
            </span>
        </div>

        <div class="mp-grid">
            <section class="mp-panel" aria-labelledby="mp-title">
                <header class="mp-header">
                    <div class="mp-heading">
                        <p class="mp-eyebrow">Mercado Pago · Checkout API</p>
                        <h1 id="mp-title">Finaliza tu compra</h1>
                        <p class="mp-lede">
                            Un último paso. Tus datos de tarjeta se procesan dentro de un entorno seguro;
                            Brevare no almacena el número completo.
                        </p>
                    </div>
                    <div class="mp-order-pill">
                        <span>{{ app()->getLocale() === 'en' ? 'Order' : 'Pedido' }}</span>
                        <strong>{{ $order->order_number }}</strong>
                    </div>
                </header>

                <div class="mp-total-strip">
                    <span>Total a pagar</span>
                    <strong>{{ $storefront->formatFromPen((float) $order->total) }}</strong>
                    @if ($storefront->isInternational())
                        <small class="block">{{ __('storefront.prices.charged_in_soles', ['amount' => $storefront->chargedFromPen((float) $order->total)]) }}</small>
                    @endif
                </div>

                <div id="checkout-init-error" role="alert" aria-live="assertive" hidden wire:ignore
                    class="mp-init-error"></div>

                <div class="mp-form-card">
                    <div class="mp-form-title">
                        <span class="mp-step">01</span>
                        <div>
                            <h2>Datos de la tarjeta</h2>
                            <p>Ingresa los datos del titular para completar el pago.</p>
                        </div>
                        <flux:icon name="lock-closed" class="mp-form-lock" />
                    </div>

                    @php
                        $mpTestMode = str_starts_with((string) config('payments.mercadopago.public_key'), 'TEST-');
                    @endphp

                    @if ($mpTestMode)
                        <div class="mp-test-note">
                            <strong>Modo prueba.</strong>
                            Usa el nombre <strong>APRO</strong> y el DNI <strong>123456789</strong>
                            con las tarjetas de prueba. No se cobra dinero real.
                        </div>
                    @endif

                    <div wire:ignore>
                        <form id="form-checkout"
                            data-mp-public-key-source="runtime-endpoint"
                            data-mp-payer-email-source="application"
                            data-mp-payer-identification-source="form"
                            data-mp-identification-type="DNI"
                            class="mp-form">
                            <div class="mp-field" data-mp-field="cardNumber" role="group" aria-labelledby="card-number-label">
                                <label id="card-number-label" class="mp-label">Número de tarjeta</label>
                                <div id="form-checkout__cardNumber" data-mp-secure-field="cardNumber"
                                    class="mp-secure-input" aria-labelledby="card-number-label"></div>
                            </div>

                            <div class="mp-row">
                                <div class="mp-field" data-mp-field="expirationDate" role="group" aria-labelledby="expiration-label">
                                    <label id="expiration-label" class="mp-label">Vencimiento (MM/AA)</label>
                                    <div id="form-checkout__expirationDate" data-mp-secure-field="expirationDate"
                                        class="mp-secure-input" aria-labelledby="expiration-label"></div>
                                </div>

                                <div class="mp-field" data-mp-field="securityCode" role="group" aria-labelledby="security-code-label">
                                    <label id="security-code-label" class="mp-label">Código de seguridad (CVV)</label>
                                    <div id="form-checkout__securityCode" data-mp-secure-field="securityCode"
                                        class="mp-secure-input" aria-labelledby="security-code-label"></div>
                                </div>
                            </div>

                            <div class="mp-field" data-mp-field="cardholderName">
                                <label for="form-checkout__cardholderName" class="mp-label">Nombre del titular</label>
                                <input type="text" id="form-checkout__cardholderName" autocomplete="cc-name" required
                                    class="mp-input">
                            </div>

                            <div class="mp-field" data-mp-field="identificationNumber">
                                <label for="form-checkout__identificationNumber" class="mp-label">DNI del titular</label>
                                <input type="text" id="form-checkout__identificationNumber" inputmode="numeric"
                                    autocomplete="off" maxlength="12" required class="mp-input">
                            </div>

                            <select id="form-checkout__issuer" data-mp-sdk-required-field="issuer"
                                hidden aria-hidden="true" tabindex="-1"></select>
                            <select id="form-checkout__installments" data-mp-sdk-required-field="installments"
                                hidden aria-hidden="true" tabindex="-1"></select>
                            <select id="form-checkout__identificationType" data-mp-sdk-required-field="identificationType"
                                hidden aria-hidden="true" tabindex="-1">
                                <option value="DNI">DNI</option>
                            </select>

                            <button type="submit" id="form-checkout__submit"
                                data-default-label="{{ app()->getLocale() === 'en' ? 'Pay S/' : 'Pagar S/' }} {{ number_format((float) $order->total, 2) }}"
                                class="mp-submit">
                                {{ app()->getLocale() === 'en' ? 'Pay S/' : 'Pagar S/' }} {{ number_format((float) $order->total, 2) }}
                            </button>
                        </form>
                    </div>

                    <div id="mp-checkout-result" role="status" aria-live="polite" hidden wire:ignore
                        class="mp-result"></div>

                    <div class="mp-trust">
                        <span class="mp-trust-item">
                            <flux:icon name="lock-closed" class="size-3.5" />
                            Cifrado seguro
                        </span>
                        <span class="mp-trust-item">
                            <flux:icon name="shield-check" class="size-3.5" />
                            Compra protegida
                        </span>
                        <span class="mp-trust-item">
                            <flux:icon name="credit-card" class="size-3.5" />
                            Tarjeta tokenizada
                        </span>
                    </div>
                </div>

                <div class="mp-links">
                    <a href="{{ route('account.orders') }}">Mis pedidos</a>
                    <a href="{{ route('store.cart') }}">Ver mi carrito</a>
                </div>
            </section>

            <aside class="mp-side">
                <div class="mp-glass mp-summary">
                    <div class="mp-summary-head">
                        <h2>{{ __('storefront.checkout.summary') }}</h2>
                        <span>{{ $order->order_number }}</span>
                    </div>

                    <div class="mp-summary-items">
                        @foreach ($order->items as $item)
                            <div class="mp-summary-item">
                                <span>{{ $item->quantity }} × {{ $item->product_name }}</span>
                                <strong><x-brevare.price :amount="(float) $item->line_subtotal" size="xs" class="font-bold text-gray-900" /></strong>
                            </div>
                        @endforeach
                    </div>

                    <dl class="mp-summary-totals">
                        <div>
                            <dt>{{ __('storefront.common.subtotal') }}</dt>
                            <dd><x-brevare.price :amount="(float) $order->subtotal" size="xs" class="font-semibold text-gray-900" /></dd>
                        </div>
                        <div>
                            <dt>{{ __('storefront.checkout.shipping') }}</dt>
                            <dd><x-brevare.price :amount="(float) $order->shipping_total" size="xs" class="font-semibold text-gray-900" /></dd>
                        </div>
                        @if ((float) $order->discount_total > 0)
                            <div class="mp-summary-discount">
                                <dt>Descuento</dt>
                                <dd>- <x-brevare.price :amount="(float) $order->discount_total" size="xs" class="font-semibold text-green-700" /></dd>
                            </div>
                        @endif
                    </dl>

                    <div class="mp-summary-total">
                        <span>Total</span>
                        <strong>{{ $storefront->formatFromPen((float) $order->total) }}</strong>
                    </div>
                    <p class="mp-summary-note">
                        <flux:icon name="check" class="size-3.5" />
                        Vendido y respaldado por Brevare
                    </p>
                </div>

                <div class="mp-glass mp-assurance">
                    <span class="mp-assurance-icon">
                        <flux:icon name="shield-check" class="size-5" />
                    </span>
                    <div>
                        <h2>Compra protegida</h2>
                        <p>Mercado Pago procesa el pago con cifrado y validación de seguridad en cada transacción.</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .mp-checkout {
            --mp-ink: #17130f;
            --mp-muted: #6f6558;
            --mp-cream: #f6efe4;
            --mp-yellow: #f6c84b;
            --mp-coral: #df795e;
            --mp-line: rgba(23, 19, 15, 0.09);
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(1100px 560px at 8% -10%, rgba(246, 200, 75, 0.42), transparent 62%),
                radial-gradient(900px 520px at 96% 8%, rgba(223, 121, 94, 0.14), transparent 64%),
                linear-gradient(180deg, #f9f3e9 0%, #f4ecdf 100%);
            color: var(--mp-ink);
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Inter", "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        .mp-ambient {
            position: absolute;
            inset: 0;
            z-index: 0;
            overflow: hidden;
        }

        .mp-orb {
            position: absolute;
            display: block;
            border-radius: 999px;
            filter: blur(62px);
            animation: mp-float 18s ease-in-out infinite alternate;
        }

        .mp-orb--sun {
            top: -110px;
            right: 6%;
            width: 300px;
            height: 300px;
            background: rgba(246, 200, 75, 0.38);
        }

        .mp-orb--coral {
            bottom: 4%;
            left: -130px;
            width: 280px;
            height: 280px;
            background: rgba(223, 121, 94, 0.16);
            animation-delay: -7s;
        }

        .mp-shell {
            position: relative;
            z-index: 1;
            width: min(1160px, 100%);
            margin: 0 auto;
            padding: clamp(18px, 3vw, 34px) clamp(12px, 3vw, 24px) clamp(34px, 5vw, 58px);
        }

        .mp-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
        }

        .mp-back,
        .mp-secure-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 40px;
            padding: 0 14px;
            border: 1px solid rgba(255, 255, 255, 0.88);
            border-radius: 999px;
            background: rgba(255, 252, 246, 0.62);
            box-shadow: 0 8px 24px rgba(70, 49, 18, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(18px) saturate(1.15);
            -webkit-backdrop-filter: blur(18px) saturate(1.15);
            color: var(--mp-muted);
            font-size: 12px;
            font-weight: 650;
            letter-spacing: 0.01em;
        }

        .mp-back {
            text-decoration: none;
            transition: transform 0.2s ease, color 0.2s ease, background 0.2s ease;
        }

        .mp-back:hover {
            color: var(--mp-ink);
            background: rgba(255, 252, 246, 0.9);
            transform: translateY(-1px);
        }

        .mp-secure-pill {
            color: #2f6b4f;
        }

        .mp-live-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #3d9b70;
            box-shadow: 0 0 0 4px rgba(61, 155, 112, 0.12);
        }

        .mp-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.12fr) minmax(300px, 0.88fr);
            gap: clamp(16px, 2.4vw, 24px);
            align-items: start;
        }

        .mp-panel {
            padding: clamp(20px, 3.4vw, 38px);
            border: 1px solid rgba(255, 255, 255, 0.92);
            border-radius: 32px;
            background: rgba(255, 252, 246, 0.84);
            box-shadow:
                0 30px 80px rgba(70, 49, 18, 0.12),
                inset 0 1px 0 rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(28px) saturate(1.12);
            -webkit-backdrop-filter: blur(28px) saturate(1.12);
        }

        .mp-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
        }

        .mp-heading {
            min-width: 0;
        }

        .mp-eyebrow {
            margin: 0;
            color: #8a6a2f;
            font-size: 10px;
            font-weight: 750;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .mp-heading h1 {
            margin: 8px 0 0;
            max-width: 12ch;
            font-size: clamp(2rem, 4.4vw, 2.9rem);
            font-weight: 720;
            letter-spacing: -0.055em;
            line-height: 1.02;
        }

        .mp-lede {
            max-width: 50ch;
            margin: 12px 0 0;
            color: var(--mp-muted);
            font-size: 13px;
            line-height: 1.65;
        }

        .mp-order-pill {
            flex: 0 0 auto;
            min-width: 132px;
            padding: 10px 13px;
            border: 1px solid var(--mp-line);
            border-radius: 16px;
            background: rgba(23, 19, 15, 0.045);
            text-align: right;
        }

        .mp-order-pill span {
            display: block;
            color: #8a8175;
            font-size: 9px;
            font-weight: 750;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .mp-order-pill strong {
            display: block;
            margin-top: 3px;
            color: var(--mp-ink);
            font-size: 11px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.02em;
        }

        .mp-total-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 22px;
            padding: 13px 17px;
            border: 1px solid rgba(246, 200, 75, 0.42);
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(246, 200, 75, 0.26), rgba(255, 252, 246, 0.62));
        }

        .mp-total-strip span {
            color: #796b55;
            font-size: 10px;
            font-weight: 750;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .mp-total-strip strong {
            color: var(--mp-ink);
            font-size: 19px;
            font-weight: 720;
            letter-spacing: -0.035em;
            font-variant-numeric: tabular-nums;
        }

        .mp-init-error,
        .mp-result {
            margin-top: 14px;
            padding: 12px 14px;
            border: 1px solid var(--mp-line);
            border-radius: 14px;
            background: rgba(255, 252, 246, 0.8);
            color: #5f574c;
            font-size: 12px;
            line-height: 1.55;
        }

        .mp-init-error {
            border-color: rgba(190, 62, 62, 0.2);
            background: rgba(254, 242, 242, 0.92);
            color: #8f2f2f;
        }

        .mp-result.is-error {
            border-color: rgba(190, 62, 62, 0.2);
            background: rgba(254, 242, 242, 0.92);
            color: #8f2f2f;
        }

        .mp-form-card {
            margin-top: 16px;
            padding: clamp(16px, 2.5vw, 24px);
            border: 1px solid rgba(23, 19, 15, 0.075);
            border-radius: 24px;
            background: #fffdf8;
            box-shadow: 0 14px 30px rgba(70, 49, 18, 0.055), inset 0 1px 0 #fff;
        }

        .mp-test-note {
            margin-bottom: 16px;
            padding: 11px 13px;
            border: 1px solid rgba(246, 200, 75, 0.46);
            border-radius: 14px;
            background: rgba(246, 200, 75, 0.16);
            color: #6d5520;
            font-size: 11px;
            line-height: 1.55;
        }

        .mp-test-note strong {
            color: #4d3b11;
            font-weight: 750;
        }

        .mp-form-title {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 18px;
        }

        .mp-step {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            border-radius: 999px;
            background: var(--mp-yellow);
            color: #3c2c05;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        .mp-form-title h2 {
            margin: 0;
            color: var(--mp-ink);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .mp-form-title p {
            margin: 2px 0 0;
            color: #8a8175;
            font-size: 11px;
        }

        .mp-form-lock {
            width: 17px;
            height: 17px;
            margin-left: auto;
            color: #9a8f80;
        }

        .mp-form {
            display: grid;
            gap: 15px;
        }

        .mp-field {
            min-width: 0;
        }

        .mp-label {
            display: block;
            margin-bottom: 7px;
            color: #5f574c;
            font-size: 11px;
            font-weight: 650;
            letter-spacing: 0.015em;
        }

        .mp-input,
        .mp-secure-input {
            width: 100%;
            height: 52px;
            min-height: 52px;
            border: 1px solid rgba(23, 19, 15, 0.14);
            border-radius: 16px;
            background: #fff;
            color: var(--mp-ink);
            font: inherit;
            font-size: 15px;
            box-shadow: inset 0 1px 2px rgba(23, 19, 15, 0.035);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .mp-input {
            padding: 0 15px;
        }

        .mp-input:focus,
        .mp-secure-input:focus-within {
            outline: none;
            border-color: rgba(172, 126, 25, 0.55);
            background: #fffefa;
            box-shadow: 0 0 0 4px rgba(246, 200, 75, 0.22);
        }

        .mp-secure-input {
            position: relative;
            overflow: hidden;
        }

        .mp-secure-input iframe {
            display: block !important;
            width: 100% !important;
            height: 52px !important;
            min-height: 52px !important;
            max-height: 52px !important;
            border: 0 !important;
        }

        .mp-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 13px;
        }

        .mp-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 58px;
            margin-top: 3px;
            border: 0;
            border-radius: 999px;
            background: var(--mp-ink);
            background-image: linear-gradient(180deg, rgba(255, 255, 255, 0.12), transparent 55%);
            color: #fffdf8;
            box-shadow: 0 15px 30px rgba(23, 19, 15, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            cursor: pointer;
            font: inherit;
            font-size: 15px;
            font-weight: 720;
            letter-spacing: -0.015em;
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
        }

        .mp-submit:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 19px 36px rgba(23, 19, 15, 0.24), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .mp-submit:active:not(:disabled) {
            transform: scale(0.992);
        }

        .mp-submit:focus-visible {
            outline: 3px solid rgba(246, 200, 75, 0.55);
            outline-offset: 3px;
        }

        .mp-submit:disabled {
            cursor: wait;
            opacity: 0.64;
        }

        .mp-trust {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid var(--mp-line);
        }

        .mp-trust-item {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            color: #7c7264;
            font-size: 10px;
            line-height: 1.4;
        }

        .mp-trust-item svg {
            flex: 0 0 auto;
            color: #8a6a2f;
        }

        .mp-links {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 18px;
            font-size: 11px;
        }

        .mp-links a {
            color: #7c7264;
            text-decoration: underline;
            text-decoration-color: rgba(23, 19, 15, 0.2);
            text-underline-offset: 4px;
            transition: color 0.2s ease;
        }

        .mp-links a:hover {
            color: var(--mp-ink);
        }

        .mp-side {
            display: grid;
            gap: 16px;
            position: sticky;
            top: 92px;
        }

        .mp-glass {
            border: 1px solid rgba(255, 255, 255, 0.92);
            border-radius: 28px;
            background: rgba(255, 252, 246, 0.66);
            box-shadow:
                0 24px 60px rgba(70, 49, 18, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(24px) saturate(1.1);
            -webkit-backdrop-filter: blur(24px) saturate(1.1);
        }

        .mp-summary {
            padding: 22px;
        }

        .mp-summary-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 10px;
        }

        .mp-summary-head h2 {
            margin: 0;
            color: var(--mp-ink);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .mp-summary-head span {
            color: #8a6a2f;
            font-size: 9px;
            font-weight: 750;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .mp-summary-items {
            display: grid;
            gap: 9px;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid var(--mp-line);
        }

        .mp-summary-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: #655c50;
            font-size: 12px;
            line-height: 1.45;
        }

        .mp-summary-item span {
            min-width: 0;
        }

        .mp-summary-item strong {
            flex: 0 0 auto;
            color: var(--mp-ink);
            font-weight: 650;
            font-variant-numeric: tabular-nums;
        }

        .mp-summary-totals {
            display: grid;
            gap: 7px;
            margin: 16px 0 0;
            padding-top: 14px;
            border-top: 1px solid var(--mp-line);
        }

        .mp-summary-totals div {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            color: #7c7264;
            font-size: 11px;
        }

        .mp-summary-totals dt,
        .mp-summary-totals dd {
            margin: 0;
        }

        .mp-summary-totals dd {
            color: var(--mp-ink);
            font-variant-numeric: tabular-nums;
        }

        .mp-summary-discount dd {
            color: #2f6b4f;
        }

        .mp-summary-total {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 10px;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid var(--mp-line);
            color: var(--mp-ink);
            font-size: 12px;
            font-weight: 650;
        }

        .mp-summary-total strong {
            font-size: 23px;
            font-weight: 740;
            letter-spacing: -0.045em;
            font-variant-numeric: tabular-nums;
        }

        .mp-summary-note {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 11px 0 0;
            color: #8a8175;
            font-size: 10px;
        }

        .mp-assurance {
            display: flex;
            align-items: flex-start;
            gap: 13px;
            padding: 19px;
        }

        .mp-assurance-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            border-radius: 999px;
            background: rgba(47, 107, 79, 0.1);
            color: #2f6b4f;
        }

        .mp-assurance h2 {
            margin: 1px 0 0;
            color: var(--mp-ink);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .mp-assurance p {
            margin: 5px 0 0;
            color: #7c7264;
            font-size: 11px;
            line-height: 1.55;
        }

        @keyframes mp-float {
            from { transform: translate3d(0, 0, 0) scale(1); }
            to { transform: translate3d(0, 18px, 0) scale(1.06); }
        }

        @media (max-width: 980px) {
            .mp-grid {
                grid-template-columns: 1fr;
            }

            .mp-side {
                position: static;
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 720px) {
            .mp-shell {
                padding: 14px 10px 36px;
            }

            .mp-topbar {
                margin-bottom: 12px;
            }

            .mp-panel {
                border-radius: 24px;
                padding: 18px;
            }

            .mp-header {
                flex-direction: column;
                gap: 13px;
            }

            .mp-order-pill {
                min-width: 0;
                text-align: left;
            }

            .mp-heading h1 {
                max-width: none;
                font-size: clamp(1.85rem, 9vw, 2.4rem);
            }

            .mp-row {
                grid-template-columns: 1fr;
            }

            .mp-trust {
                grid-template-columns: 1fr;
            }

            .mp-side {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 420px) {
            .mp-secure-pill {
                display: none;
            }

            .mp-panel {
                padding: 15px;
            }

            .mp-form-card {
                padding: 14px;
            }

            .mp-total-strip strong {
                font-size: 17px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .mp-orb {
                animation: none;
            }

            .mp-back,
            .mp-input,
            .mp-secure-input,
            .mp-submit,
            .mp-links a {
                transition: none;
            }
        }
    </style>
@endpush
