<div class="mx-auto max-w-7xl">
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <header class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 px-6 py-5">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-gray-900">Productos</h1>
                <p class="mt-0.5 text-sm text-gray-500">Administra el catálogo de productos.</p>
            </div>

            <livewire:admin.catalog.products.form />

            <button wire:click="$dispatch('product-create')"
                class="inline-flex items-center gap-1.5 rounded-full bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                <flux:icon name="plus" class="size-4" /> Nuevo producto
            </button>
        </header>

        <div class="border-b border-gray-100 p-6">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative max-w-sm flex-1">
                    <flux:icon name="magnifying-glass" variant="mini" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                    <input type="text" placeholder="Buscar..." wire:model.live.debounce.300ms="search"
                        class="w-full rounded-full border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                </div>

                <div class="relative">
                    <select wire:model.live="categoryId" aria-label="Filtrar por categoría"
                        wire:loading.attr="disabled" wire:target="search,categoryId,brandId,sort,perPage"
                        class="w-full appearance-none rounded-full border border-gray-300 bg-white py-2 pl-4 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                        <option value="">Todas las categorías</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                </div>

                <div class="relative">
                    <select wire:model.live="brandId" aria-label="Filtrar por marca"
                        wire:loading.attr="disabled" wire:target="search,categoryId,brandId,sort,perPage"
                        class="w-full appearance-none rounded-full border border-gray-300 bg-white py-2 pl-4 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                        <option value="">Todas las marcas</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                </div>

                <div class="relative">
                    <select wire:model.live="sort" aria-label="Ordenar productos"
                        wire:loading.attr="disabled" wire:target="search,categoryId,brandId,sort,perPage"
                        class="w-full appearance-none rounded-full border border-gray-300 bg-white py-2 pl-4 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                        <option value="latest">Más recientes</option>
                        <option value="web_visits_desc">Más visitas web (30 d)</option>
                        <option value="meta_visits_desc">Más visitas desde Meta (30 d)</option>
                    </select>
                    <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                </div>

                <div class="relative w-20">
                    <select wire:model.live="perPage" aria-label="Productos por página"
                        wire:loading.attr="disabled" wire:target="search,categoryId,brandId,sort,perPage"
                        class="w-full appearance-none rounded-full border border-gray-300 bg-white py-2 pl-4 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                </div>

                <span wire:loading wire:target="search,categoryId,brandId,sort,perPage" role="status"
                    class="inline-flex items-center gap-2 text-sm font-medium text-gray-600">
                    <span class="size-4 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900" aria-hidden="true"></span>
                    Actualizando…
                </span>
            </div>
        </div>

        @include('livewire.admin.catalog.products.table')
    </div>
</div>
