@push('styles')
    <link rel="stylesheet" href="{{ $cssUrl }}">
@endpush

@push('scripts')
    {{-- El SDK de Izipay exige la clave pública en el propio script: el
         formulario no se puede dibujar desde el servidor. --}}
    <script type="text/javascript"
        src="{{ $jsUrl }}"
        kr-public-key="{{ $publicKey }}"
        kr-post-url-success="{{ $returnUrl }}"
        kr-post-url-refused="{{ $returnUrl }}">
    </script>
    <script type="text/javascript" src="{{ $themeUrl }}"></script>
@endpush

<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <nav aria-label="breadcrumb" class="mb-5 text-sm text-gray-500">
        <a href="{{ route('checkout') }}" class="transition hover:text-gray-900">Finalizar compra</a>
        <span class="mx-1.5" aria-hidden="true">/</span>
        <span class="font-medium text-gray-900">Pago con Izipay</span>
    </nav>

    <div class="grid gap-6 lg:grid-cols-[1.35fr_1fr]">
        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-900/70">Pago seguro</p>
                    <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">
                        Completa tu pago
                    </h1>
                    <p class="mt-1.5 text-sm text-gray-500">
                        Elige tarjeta, Yape o el medio que prefieras dentro del formulario de Izipay.
                    </p>
                </div>

                <div class="rounded-2xl bg-[#f8f5ed] px-4 py-3 text-right">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-900/70">Pedido</p>
                    <p class="font-mono text-sm font-bold text-gray-900">{{ $order->order_number }}</p>
                    <p class="mt-0.5 text-lg font-extrabold text-gray-900">
                        S/ {{ number_format((float) $order->total, 2) }}
                    </p>
                </div>
            </div>

            <div class="mt-6 rounded-2xl border border-gray-200 bg-gray-50/60 p-4 sm:p-6">
                @if ($formToken !== '')
                    {{-- wire:ignore evita que Livewire reemplace el formulario
                         que el SDK de Izipay construye dentro de este nodo. --}}
                    <div wire:ignore>
                        <div class="kr-smart-form" kr-form-token="{{ $formToken }}"></div>
                    </div>
                @else
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        No pudimos abrir el formulario de pago. Vuelve al checkout e inténtalo otra vez.
                    </div>
                @endif
            </div>

            <p class="mt-4 flex items-start gap-2 text-xs text-gray-500">
                <flux:icon name="lock-closed" class="size-4 shrink-0" />
                Pago procesado por Izipay. Brevare no almacena los datos de tu tarjeta.
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <a href="{{ route('checkout') }}"
                    class="inline-flex min-h-11 items-center rounded-full border border-gray-300 px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    Volver al checkout
                </a>
                <a href="{{ route('store.cart') }}"
                    class="text-sm font-medium text-gray-500 underline-offset-4 transition hover:text-gray-900 hover:underline">
                    Ver mi carrito
                </a>
            </div>
        </section>

        <aside class="h-fit space-y-4">
            <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="font-bold text-gray-900">Resumen del pedido</h2>

                <div class="mt-4 space-y-2 border-t border-gray-100 pt-4 text-sm">
                    @foreach ($order->items as $item)
                        <div class="flex justify-between gap-3">
                            <span class="min-w-0 truncate text-gray-700">{{ $item->quantity }} × {{ $item->product_name }}</span>
                            <strong class="shrink-0 text-gray-900">S/ {{ number_format((float) $item->line_subtotal, 2) }}</strong>
                        </div>
                    @endforeach
                </div>

                <dl class="mt-4 space-y-1.5 border-t border-gray-100 pt-4 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Subtotal</dt>
                        <dd class="text-gray-900">S/ {{ number_format((float) $order->subtotal, 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Envío</dt>
                        <dd class="text-gray-900">S/ {{ number_format((float) $order->shipping_total, 2) }}</dd>
                    </div>
                    @if ((float) $order->discount_total > 0)
                        <div class="flex justify-between text-emerald-700">
                            <dt>Descuentos</dt>
                            <dd>- S/ {{ number_format((float) $order->discount_total, 2) }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
                    <span class="font-bold text-gray-900">Total</span>
                    <span class="text-2xl font-extrabold tracking-tight text-gray-900">
                        S/ {{ number_format((float) $order->total, 2) }}
                    </span>
                </div>
            </div>

            <x-brevare.payment-methods />

            <div class="rounded-3xl border border-gray-200 bg-white p-6 text-sm text-gray-600 shadow-sm">
                <p class="flex items-start gap-2">
                    <flux:icon name="information-circle" class="mt-0.5 size-4 shrink-0 text-gray-400" />
                    <span>
                        Tu pedido queda reservado mientras completas el pago. Si cierras esta página, puedes
                        retomarlo desde <a href="{{ route('account.orders') }}" class="font-medium text-gray-900 underline underline-offset-4">Mis pedidos</a>.
                    </span>
                </p>
            </div>
        </aside>
    </div>
</div>
