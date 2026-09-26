<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Migas de pan --}}
        <nav aria-label="breadcrumb" class="mb-3 text-sm">
            <ol class="flex flex-wrap items-center gap-1.5 text-gray-500">
                <li><a href="{{ route('home') }}" class="transition hover:text-gray-900">Inicio</a></li>
                <li><flux:icon name="chevron-right" variant="mini" class="size-3.5" /></li>
                <li><a href="{{ route('store.search') }}" class="transition hover:text-gray-900">Catálogo</a></li>
                @if ($currentCategory)
                    @if ($currentCategory->parent)
                        <li><flux:icon name="chevron-right" variant="mini" class="size-3.5" /></li>
                        <li><a href="{{ route('store.category', ['path' => $currentCategory->parent->path]) }}" class="transition hover:text-gray-900">{{ $currentCategory->parent->name }}</a></li>
                    @endif
                    <li><flux:icon name="chevron-right" variant="mini" class="size-3.5" /></li>
                    <li class="font-medium text-gray-900" aria-current="page">{{ $currentCategory->name }}</li>
                @elseif ($search)
                    <li><flux:icon name="chevron-right" variant="mini" class="size-3.5" /></li>
                    <li class="font-medium text-gray-900" aria-current="page">Resultados para "{{ $search }}"</li>
                @endif
            </ol>
        </nav>

        <h1 class="mb-5 text-2xl font-bold tracking-tight text-gray-900">
            {{ $currentCategory?->name ?? ($search ? 'Resultados de búsqueda' : 'Catálogo completo') }}
        </h1>

        @if ($subcategories->isNotEmpty())
            <div class="mb-6 flex flex-wrap gap-2">
                @foreach ($subcategories as $sub)
                    <a href="{{ route('store.category', ['path' => $sub->path]) }}"
                        class="rounded-full px-4 py-2 text-sm font-medium transition {{ $categoryPath === $sub->path ? 'bg-gray-900 text-white' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}">
                        {{ $sub->name }}
                    </a>
                @endforeach
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-4">

            {{-- Filtros --}}
            <aside class="lg:col-span-1">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:sticky lg:top-24">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-900">
                            <span class="inline-flex items-center gap-1.5"><flux:icon name="funnel" class="size-4" /> Filtros</span>
                        </h2>
                        @if ($brandId || $attributeValues || $minPrice || $maxPrice || $search)
                            <button class="text-sm font-medium text-gray-600 transition hover:text-gray-900" wire:click="clearFilters">
                                Limpiar
                            </button>
                        @endif
                    </div>

                    {{-- Categorías --}}
                    <div class="mb-4">
                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">Categoría</p>
                        @foreach ($categories as $cat)
                            <a href="{{ route('store.category', ['path' => $cat->path]) }}"
                                class="mb-1 block text-sm transition {{ $categoryPath === $cat->path ? 'font-bold text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">
                                {{ $cat->name }}
                            </a>
                        @endforeach
                    </div>

                    <hr class="my-4 border-gray-100">

                    {{-- Marcas --}}
                    <div class="mb-4">
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-gray-500">Marca</label>
                        <div class="relative">
                            <select wire:model.live="brandId"
                                class="w-full appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                <option value="">Todas</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                            <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                        </div>
                    </div>

                    {{-- Precio --}}
                    <div class="mb-4">
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-gray-500">Precio (S/)</label>
                        <div class="flex items-center gap-2">
                            <input type="number" wire:model.live.debounce.500ms="minPrice" placeholder="Mín" min="0" step="0.01"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm placeholder:text-gray-400 focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            <span class="text-gray-400">-</span>
                            <input type="number" wire:model.live.debounce.500ms="maxPrice" placeholder="Máx" min="0" step="0.01"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm placeholder:text-gray-400 focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                        </div>
                    </div>

                    {{-- Atributos --}}
                    @foreach ($attributes as $attribute)
                        <hr class="my-4 border-gray-100">
                        <div class="mb-1">
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">{{ $attribute->name }}</p>
                            @foreach ($attribute->values as $value)
                                <label class="mb-1.5 flex cursor-pointer items-center gap-2 text-sm text-gray-700" for="attr-{{ $attribute->id }}-{{ $value->id }}">
                                    <input type="checkbox"
                                        wire:model.live="attributeValues.{{ $attribute->id }}"
                                        value="{{ $value->id }}" id="attr-{{ $attribute->id }}-{{ $value->id }}"
                                        class="size-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900/30">
                                    {{ $value->value }}
                                    @if ($value->color)
                                        <span class="inline-block size-3 rounded-full" style="background:{{ $value->color }};"></span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </aside>

            {{-- Productos --}}
            <div class="lg:col-span-3">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <span class="text-sm text-gray-500">{{ $products->total() }} producto(s)</span>
                    <div class="relative">
                        <select wire:model.live="sort"
                            class="appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 pr-9 text-sm text-gray-900 focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            <option value="latest">Más recientes</option>
                            <option value="price_asc">Precio: menor a mayor</option>
                            <option value="price_desc">Precio: mayor a menor</option>
                            <option value="name">Nombre (A-Z)</option>
                        </select>
                        <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                    </div>
                </div>

                @if ($products->isEmpty())
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white py-16 text-center">
                        <flux:icon name="magnifying-glass" class="mx-auto mb-3 size-10 text-gray-300" />
                        <p class="text-sm font-medium text-gray-700">No encontramos productos con esos filtros.</p>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($products as $product)
                            @include('store.components.product-card', ['product' => $product])
                        @endforeach
                    </div>
                    @if ($products->hasPages())
                        <div class="mt-6">
                            {{ $products->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>