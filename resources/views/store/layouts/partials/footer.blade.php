<footer class="mt-auto border-t border-gray-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-bold tracking-tight text-gray-900">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-gray-900 text-white">
                        <flux:icon name="shopping-bag" class="size-4" />
                    </span>
                    Bravera
                </a>
                <p class="mt-3 max-w-sm text-sm leading-relaxed text-gray-500">
                    Marketplace de productos con envío a todo el Perú.
                </p>
            </div>

            <div>
                <p class="text-sm font-semibold text-gray-900">Tienda</p>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('home') }}">Inicio</a></li>
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('store.search') }}">Catálogo</a></li>
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('store.cart') }}">Carrito</a></li>
                </ul>
            </div>

            <div>
                <p class="text-sm font-semibold text-gray-900">Mi cuenta</p>
                <ul class="mt-4 space-y-3 text-sm">
                    @auth
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('account.orders') }}">Mis pedidos</a></li>
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('account.addresses') }}">Mis direcciones</a></li>
                    @else
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('login') }}">Iniciar sesión</a></li>
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('register') }}">Crear cuenta</a></li>
                    @endauth
                </ul>
            </div>
        </div>

        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-gray-100 pt-6 sm:flex-row">
            <p class="text-sm text-gray-400">&copy; {{ date('Y') }} Bravera. Todos los derechos reservados.</p>
            <p class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-sm text-gray-400">
                <span class="flex items-center gap-1"><flux:icon name="credit-card" class="size-4" /> Tarjeta</span>
                <span class="flex items-center gap-1"><flux:icon name="device-phone-mobile" class="size-4" /> Yape / Plin</span>
                <span class="flex items-center gap-1"><flux:icon name="banknotes" class="size-4" /> Efectivo</span>
            </p>
        </div>
    </div>
</footer>