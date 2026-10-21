<footer class="mt-auto border-t border-gray-200 bg-white">
    <div class="bg-amber-300">
        <div class="mx-auto grid max-w-7xl gap-3 px-4 py-4 text-sm font-semibold text-amber-950 sm:grid-cols-3 sm:px-6 lg:px-8">
            <p class="flex items-center gap-2"><flux:icon name="truck" class="size-5" /> Envíos a todo el Perú</p>
            <p class="flex items-center gap-2"><flux:icon name="lock-closed" class="size-5" /> Compra con pago seguro</p>
            <p class="flex items-center gap-2"><flux:icon name="chat-bubble-left-right" class="size-5" /> Atención para acompañarte</p>
        </div>
    </div>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-bold tracking-tight text-gray-900">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-amber-300 text-amber-950">
                        <flux:icon name="shopping-bag" class="size-4" />
                    </span>
                    Brevare
                </a>
                <p class="mt-3 max-w-sm text-sm leading-relaxed text-gray-500">
                    <span class="font-medium text-gray-700">Todo lo que buscas, en un solo lugar.</span><br>
                    Novedades, regalos y productos para cada momento, con envío a todo el Perú.
                </p>
                <a href="{{ route('store.search') }}" class="mt-5 inline-flex min-h-11 items-center gap-2 rounded-full bg-gray-950 px-5 text-sm font-semibold text-white transition hover:bg-amber-700">Explorar productos <span aria-hidden="true">→</span></a>
                @php
                    $company = config('brevare.company');
                @endphp
                <ul class="mt-5 space-y-1 text-sm text-gray-500">
                    <li><a class="transition hover:text-gray-900" href="mailto:{{ $company['support_email'] }}">{{ $company['support_email'] }}</a></li>
                    <li><a class="transition hover:text-gray-900" href="tel:{{ $company['phone_e164'] }}">{{ $company['phone'] }}</a></li>
                    <li>{{ $company['address'] }}, {{ $company['city'] }} - {{ $company['country_name'] }}</li>
                </ul>
                @php
                    $socialLinks = collect(config('seo.social_links', []))
                        ->filter(fn ($network) => is_array($network)
                            && filled($network['url'] ?? null)
                            && filter_var($network['url'], FILTER_VALIDATE_URL)
                            && filled($network['network'] ?? null)
                            && filled($network['name'] ?? null))
                        ->values();
                @endphp
                @if ($socialLinks->isNotEmpty())
                    <div class="mt-6 border-t border-stone-200/80 pt-5">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <p class="text-[10px] font-bold uppercase tracking-[.18em] text-amber-900/75">Síguenos</p>
                            <p class="text-xs text-stone-500">Novedades y hallazgos en tus redes.</p>
                        </div>
                        <ul class="mt-3 flex flex-wrap gap-2" aria-label="Redes sociales de Brevare">
                            @foreach ($socialLinks as $social)
                                <li>
                                    <a href="{{ $social['url'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="Seguir a Brevare en {{ $social['name'] }}"
                                        title="Brevare en {{ $social['name'] }}"
                                        class="group flex size-11 items-center justify-center rounded-full border border-stone-200 bg-stone-50 shadow-[0_1px_0_rgba(28,25,23,.04)] transition duration-300 hover:-translate-y-0.5 hover:border-amber-300 hover:bg-white hover:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700 motion-reduce:transform-none motion-reduce:transition-none">
                                        <img src="{{ asset('images/social/'.$social['network'].'.svg') }}"
                                            alt=""
                                            width="20"
                                            height="20"
                                            loading="lazy"
                                            decoding="async"
                                            class="size-5 grayscale transition duration-300 group-hover:scale-110 group-hover:grayscale-0 motion-reduce:transform-none motion-reduce:transition-none">
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div>
                <p class="text-sm font-semibold text-gray-900">Tienda</p>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('home') }}">Inicio</a></li>
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('store.search') }}">Catálogo</a></li>
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('store.cart') }}">Carrito</a></li>
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('about') }}">Nosotros</a></li>
                    @foreach (\App\Support\Seo\CatalogLinks::curated() as $category)
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('store.category', ['path' => $category->path]) }}">{{ $category->seo_label ?? $category->name }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <p class="text-sm font-semibold text-gray-900">Mi cuenta</p>
                <ul class="mt-4 space-y-3 text-sm">
                    @auth
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('account.orders') }}">Mis pedidos</a></li>
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('account.addresses') }}">Mis direcciones</a></li>
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('account.points') }}">Brevare Puntos</a></li>
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('account.favorites') }}">Favoritos</a></li>
                    @else
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('login') }}">Iniciar sesión</a></li>
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('register') }}">Crear cuenta</a></li>
                    @endauth
                </ul>
            </div>

            <div>
                <p class="text-sm font-semibold text-gray-900">¿Necesitas ayuda?</p>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('claims') }}">Libro de Reclamaciones</a></li>
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('returns') }}">Cambios y devoluciones</a></li>
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('terms') }}">Información de compra</a></li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-900">Legal</p>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer">Términos y Condiciones</a></li>
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('returns') }}" target="_blank" rel="noopener noreferrer">Política de Cambios y Devoluciones</a></li>
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('privacy') }}" target="_blank" rel="noopener noreferrer">Política de Privacidad</a></li>
                    <li><button type="button" onclick="window.BrevareConsent?.open()" class="text-gray-500 transition hover:text-gray-900">Preferencias de cookies</button></li>
                    <li><a class="text-sm font-medium text-amber-600 transition hover:text-amber-700" href="{{ route('claims') }}" target="_blank" rel="noopener noreferrer">Libro de Reclamaciones</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-gray-100 pt-6 sm:flex-row">
            <p class="text-sm text-gray-400">&copy; {{ date('Y') }} {{ $company['legal_name'] }} · RUC {{ $company['ruc'] }}. Todos los derechos reservados.</p>
            <p class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-sm text-gray-400">
                <span class="flex items-center gap-1"><flux:icon name="credit-card" class="size-4" /> Tarjeta</span>
                <span class="flex items-center gap-1"><flux:icon name="device-phone-mobile" class="size-4" /> Yape / Plin</span>
                <span class="flex items-center gap-1"><flux:icon name="banknotes" class="size-4" /> Efectivo</span>
            </p>
        </div>
    </div>
</footer>
