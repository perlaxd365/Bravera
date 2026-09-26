<header class="sticky top-0 z-30 flex items-center gap-3 border-b border-gray-200 bg-white/90 px-4 py-3 backdrop-blur-lg sm:px-6">
    <button class="rounded-lg p-2 text-gray-600 transition hover:bg-gray-100 lg:hidden" type="button"
        aria-label="Abrir menú" @click="sidebarOpen = true">
        <flux:icon name="bars-3" class="size-5" />
    </button>

    <div class="text-base font-bold tracking-tight text-gray-900">Panel administrativo</div>

    <div class="ml-auto flex items-center gap-3">
        <span class="flex size-9 items-center justify-center rounded-full bg-gray-900 text-sm font-semibold text-white">
            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
        </span>
        <span class="hidden leading-tight sm:block">
            <span class="block text-sm font-semibold text-gray-900">{{ auth()->user()->name ?? 'Usuario' }}</span>
            <span class="block text-xs text-gray-500">{{ auth()->check() ? auth()->user()->email : 'No autenticado' }}</span>
        </span>
    </div>
</header>