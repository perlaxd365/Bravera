<aside
    :class="sidebarOpen ? '' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-gray-200 bg-white transition-transform duration-200 lg:translate-x-0"
    aria-label="Navegación principal">

    <div class="flex items-center gap-2.5 px-5 py-5">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
            <span class="flex size-9 items-center justify-center rounded-xl bg-gray-900 text-white">
                <flux:icon name="shopping-bag" class="size-4.5" />
            </span>
            <span class="leading-tight">
                <span class="block text-base font-bold tracking-tight text-gray-900">Brevare</span>
                <span class="block text-xs text-gray-400">Sistema Ecommerce</span>
            </span>
        </a>
        <button type="button" class="ml-auto rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 lg:hidden"
            @click="sidebarOpen = false" aria-label="Cerrar menú">
            <flux:icon name="x-mark" class="size-5" />
        </button>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-2">
        <div>
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Principal</p>
            <a href="{{ route('admin.dashboard') }}"
                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.dashboard') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <flux:icon name="squares-2x2" class="size-4.5 shrink-0" />
                Dashboard
            </a>
        </div>

        <div>
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Catálogo</p>
            <a href="{{ route('admin.catalog.categories.index') }}"
                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.catalog.categories.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <flux:icon name="tag" class="size-4.5 shrink-0" />
                Categorías
            </a>
            <a href="{{ route('admin.catalog.brands.index') }}"
                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.catalog.brands.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <flux:icon name="trophy" class="size-4.5 shrink-0" />
                Marcas
            </a>
            <a href="{{ route('admin.catalog.attributes.index') }}"
                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.catalog.attributes.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <flux:icon name="adjustments-horizontal" class="size-4.5 shrink-0" />
                Atributos
            </a>
            <a href="{{ route('admin.catalog.attribute-values.index') }}"
                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.catalog.attribute-values.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <flux:icon name="swatch" class="size-4.5 shrink-0" />
                Valores de Atributos
            </a>
            <a href="{{ route('admin.catalog.products.index') }}"
                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.catalog.products.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <flux:icon name="cube" class="size-4.5 shrink-0" />
                Productos
            </a>
            <a href="{{ route('admin.catalog.suppliers.index') }}"
                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.catalog.suppliers.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <flux:icon name="building-storefront" class="size-4.5 shrink-0" />
                Proveedores
            </a>
        </div>

        <div>
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Envíos</p>
            <a href="{{ route('admin.shipping.zones.index') }}"
                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.shipping.zones.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <flux:icon name="map-pin" class="size-4.5 shrink-0" />
                Zonas de envío
            </a>
            <a href="{{ route('admin.shipping.rates.index') }}"
                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.shipping.rates.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <flux:icon name="banknotes" class="size-4.5 shrink-0" />
                Tarifas de envío
            </a>
        </div>

        <div>
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Ventas</p>
            <a href="{{ route('admin.orders.index') }}"
                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.orders.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <flux:icon name="shopping-cart" class="size-4.5 shrink-0" />
                Pedidos
            </a>
            <a href="{{ route('admin.dropshipping.orders.index') }}"
                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.dropshipping.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <flux:icon name="paper-airplane" class="size-4.5 shrink-0" />
                Órdenes a proveedores
            </a>
            <a href="{{ route('admin.discounts.coupons.index') }}"
                class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.discounts.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <flux:icon name="ticket" class="size-4.5 shrink-0" />
                Cupones
            </a>
            <a href="#" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-gray-400">
                <flux:icon name="receipt-percent" class="size-4.5 shrink-0" />
                Comprobantes
                <span class="ml-auto rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-500">Pronto</span>
            </a>
        </div>

        <div>
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Clientes</p>
            <a href="#" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-gray-400">
                <flux:icon name="users" class="size-4.5 shrink-0" />
                Clientes
                <span class="ml-auto rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-500">Pronto</span>
            </a>
        </div>
    </nav>

    <div class="space-y-1 border-t border-gray-100 p-3">
        <a href="#" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-900">
            <flux:icon name="cog-6-tooth" class="size-4.5 shrink-0" />
            Configuración
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50">
                <flux:icon name="arrow-right-start-on-rectangle" class="size-4.5 shrink-0" />
                Cerrar sesión
            </button>
        </form>
    </div>
</aside>