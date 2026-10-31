<div
    x-data="{ filtersOpen: false }"
    x-on:catalog-filters-updated.window="
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.getElementById('catalog-top')?.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' });
        if (!reducedMotion) $nextTick(() => $refs.catalogResults?.animate(
            [{ opacity: 0.55, transform: 'translateY(8px)' }, { opacity: 1, transform: 'translateY(0)' }],
            { duration: 260, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' }
        ));
    "
>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Migas de pan --}}
        <nav aria-label="breadcrumb" class="mb-3 text-sm">
            <ol class="flex flex-wrap items-center gap-1.5 text-gray-500">
                <li><a href="{{ route('home') }}" class="transition hover:text-gray-900">{{ __('storefront.common.home') }}</a></li>
                <li><flux:icon name="chevron-right" variant="mini" class="size-3.5" /></li>
                <li><a href="{{ route('store.search') }}" class="transition hover:text-gray-900">{{ __('storefront.common.catalog') }}</a></li>
                @if ($currentCategory)
                    @if ($currentCategory->parent)
                        <li><flux:icon name="chevron-right" variant="mini" class="size-3.5" /></li>
                        <li><a href="{{ route('store.category', ['path' => $currentCategory->parent->path]) }}" class="transition hover:text-gray-900">{{ $currentCategory->parent->name }}</a></li>
                    @endif
                    <li><flux:icon name="chevron-right" variant="mini" class="size-3.5" /></li>
                    <li class="font-medium text-gray-900" aria-current="page">{{ $currentCategory->name }}</li>
                @elseif ($search)
                    <li><flux:icon name="chevron-right" variant="mini" class="size-3.5" /></li>
                    <li class="font-medium text-gray-900" aria-current="page">{{ app()->getLocale() === 'en' ? 'Results for' : 'Resultados para' }} "{{ $search }}"</li>
                @endif
            </ol>
        </nav>

        <h1 id="catalog-top" class="mb-5 text-2xl font-bold tracking-tight text-gray-900">
            {{ $currentCategory?->name ?? ($offers ? __('storefront.catalog.offers') : ($search ? __('storefront.catalog.results') : __('storefront.catalog.all'))) }}
        </h1>

        @if ($currentCategory?->description)
            <p class="mb-6 max-w-4xl text-sm leading-6 text-gray-600">{{ $currentCategory->description }}</p>
        @endif

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

        @php
            $selectedAttributeFilters = $attributes->flatMap(function ($attribute) use ($attributeValues) {
                $selectedIds = collect($attributeValues)
                    ->flatMap(fn ($selected) => is_array($selected) ? $selected : [$selected])
                    ->map(fn ($selected) => (string) $selected)
                    ->all();

                return $attribute->values
                    ->filter(fn ($value) => in_array((string) $value->id, $selectedIds, true))
                    ->map(fn ($value) => ['id' => $value->id, 'label' => $attribute->name.': '.$value->value]);
            })->values();
            $activeFilterCount = (int) filled($brandId)
                + $selectedAttributeFilters->count()
                + (int) filled($minPrice)
                + (int) filled($maxPrice);
        @endphp

        <div class="grid gap-4 lg:grid-cols-4 lg:gap-6">

            {{-- Filtros --}}
            <aside class="lg:col-span-1">
                <button
                    type="button"
                    class="flex min-h-11 w-full items-center justify-between rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm lg:hidden"
                    @click="filtersOpen = !filtersOpen"
                    :aria-expanded="filtersOpen.toString()"
                    aria-controls="catalog-filters"
                >
                    <span class="inline-flex items-center gap-2"><flux:icon name="funnel" class="size-4" /> {{ __('storefront.catalog.filters') }} @if ($activeFilterCount) <span class="rounded-full bg-amber-300 px-2 py-0.5 text-xs text-amber-950">{{ $activeFilterCount }}</span> @endif</span>
                    <flux:icon name="chevron-down" class="size-4 transition-transform" x-bind:class="filtersOpen ? 'rotate-180' : ''" />
                </button>

                <div id="catalog-filters" class="mt-3 hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-sm lg:sticky lg:top-24 lg:mt-0 lg:block lg:p-5" x-bind:class="filtersOpen ? '!block' : ''">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-gray-900">
                            <span class="inline-flex items-center gap-1.5"><flux:icon name="funnel" class="size-4" /> {{ __('storefront.catalog.filters') }}</span>
                        </h2>
                        @if ($brandId || $attributeValues || $minPrice || $maxPrice || $search)
                            <button class="text-sm font-medium text-gray-600 transition hover:text-gray-900" wire:click="clearFilters" @click="filtersOpen = false">
                                {{ app()->getLocale() === 'en' ? 'Clear' : 'Limpiar' }}
                            </button>
                        @endif
                    </div>

                    {{-- Categorías --}}
                    <div class="mb-4">
                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-500">{{ app()->getLocale() === 'en' ? 'Category' : 'Categoría' }}</p>
                        @foreach ($categories as $cat)
                            <a href="{{ route('store.category', ['path' => $cat->path]) }}"
                                class="mb-1 block text-sm transition {{ $categoryPath === $cat->path ? 'font-bold text-gray-900' : 'text-gray-600 hover:text-gray-900' }}">
                                {{ $cat->seo_label ?? $cat->name }}
                            </a>
                        @endforeach
                    </div>

                    <hr class="my-4 border-gray-100">

                    {{-- Ofertas --}}
                    <label class="mb-4 flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-800">
                        <span>{{ app()->getLocale() === 'en' ? 'Only products on sale' : 'Solo productos en oferta' }}</span>
                        <input type="checkbox" wire:model.live="offers" @change="filtersOpen = false"
                            class="size-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900/30">
                    </label>

                    {{-- Marcas --}}
                    <div class="mb-4">
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-gray-500">{{ app()->getLocale() === 'en' ? 'Brand' : 'Marca' }}</label>
                        <div class="relative">
                            <select wire:model.live="brandId" @change="filtersOpen = false"
                                class="w-full appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                <option value="">{{ app()->getLocale() === 'en' ? 'All brands' : 'Todas' }}</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                            <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                        </div>
                    </div>

                    {{-- Precio --}}
                    <div class="mb-4">
                        <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-gray-500">{{ __('storefront.catalog.price') }} ({{ $storefront->currency }})</label>
                        <div class="flex items-center gap-2">
                            <input type="number" wire:model.live.debounce.500ms="minPrice" @change="filtersOpen = false" placeholder="{{ app()->getLocale() === 'en' ? 'Min' : 'Mín' }}" min="0" step="0.01"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm placeholder:text-gray-400 focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            <span class="text-gray-400">-</span>
                            <input type="number" wire:model.live.debounce.500ms="maxPrice" @change="filtersOpen = false" placeholder="{{ app()->getLocale() === 'en' ? 'Max' : 'Máx' }}" min="0" step="0.01"
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
                                        wire:model.live="attributeValues"
                                        value="{{ $value->id }}" id="attr-{{ $attribute->id }}-{{ $value->id }}"
                                        @change="filtersOpen = false"
                                        class="size-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900/30">
                                    {{ $value->value }}
                                    @if ($value->color)
                                        <span class="inline-block size-3 rounded-full" style="background:{{ $value->color }};"></span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    @endforeach

                    <button type="button" class="mt-3 min-h-11 w-full rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800 lg:hidden" @click="filtersOpen = false">
                        {{ app()->getLocale() === 'en' ? 'View products' : 'Ver productos' }}
                    </button>
                </div>
            </aside>

            {{-- Productos --}}
            <div class="lg:col-span-3" x-ref="catalogResults">
                @if ($activeFilterCount)
                    <div class="mb-4 flex flex-wrap items-center gap-2" aria-label="{{ app()->getLocale() === 'en' ? 'Applied filters' : 'Filtros aplicados' }}">
                        <span class="mr-1 text-xs font-semibold text-gray-500">{{ app()->getLocale() === 'en' ? 'Applied:' : 'Aplicados:' }}</span>
                        @if ($brandId && $selectedBrand = $brands->firstWhere('id', $brandId))
                            <button type="button" wire:click="clearBrand" class="inline-flex min-h-8 items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-medium text-amber-950">
                                {{ $selectedBrand->name }} <span aria-hidden="true">×</span><span class="sr-only">{{ app()->getLocale() === 'en' ? 'Remove brand filter' : 'Quitar filtro de marca' }}</span>
                            </button>
                        @endif
                        @foreach ($selectedAttributeFilters as $selectedFilter)
                            <button type="button" wire:click="clearAttributeValue({{ $selectedFilter['id'] }})" class="inline-flex min-h-8 items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-medium text-amber-950">
                                {{ $selectedFilter['label'] }} <span aria-hidden="true">×</span><span class="sr-only">{{ app()->getLocale() === 'en' ? 'Remove filter' : 'Quitar filtro' }}</span>
                            </button>
                        @endforeach
                        @if (filled($minPrice) || filled($maxPrice))
                            <button type="button" wire:click="clearPriceRange" class="inline-flex min-h-8 items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-medium text-amber-950">
                                {{ __('storefront.catalog.price') }}: {{ filled($minPrice) ? $storefront->format((float) $minPrice) : $storefront->format(0) }} – {{ filled($maxPrice) ? $storefront->format((float) $maxPrice) : (app()->getLocale() === 'en' ? 'no maximum' : 'sin máximo') }} <span aria-hidden="true">×</span><span class="sr-only">{{ app()->getLocale() === 'en' ? 'Remove price filter' : 'Quitar filtro de precio' }}</span>
                            </button>
                        @endif
                    </div>
                @endif
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <span class="text-sm text-gray-500">{{ __('storefront.catalog.products_found', ['count' => $products->total()]) }}</span>
                    <div class="relative">
                        <select wire:model.live="sort"
                            class="appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 pr-9 text-sm text-gray-900 focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            <option value="latest">{{ app()->getLocale() === 'en' ? 'Newest' : 'Más recientes' }}</option>
                            <option value="web_visits_desc">{{ app()->getLocale() === 'en' ? 'Most web visits' : 'Más visitas web' }}</option>
                            <option value="meta_visits_desc">{{ app()->getLocale() === 'en' ? 'Most visits from Meta' : 'Más visitas desde Meta' }}</option>
                            <option value="price_asc">{{ app()->getLocale() === 'en' ? 'Price: low to high' : 'Precio: menor a mayor' }}</option>
                            <option value="price_desc">{{ app()->getLocale() === 'en' ? 'Price: high to low' : 'Precio: mayor a menor' }}</option>
                            <option value="name">{{ app()->getLocale() === 'en' ? 'Name (A-Z)' : 'Nombre (A-Z)' }}</option>
                        </select>
                        <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                    </div>
                </div>

                @if ($products->isEmpty())
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white py-16 text-center">
                        <flux:icon name="magnifying-glass" class="mx-auto mb-3 size-10 text-gray-300" />
                        <p class="text-base font-semibold text-gray-800">{{ app()->getLocale() === 'en' ? 'We could not find an exact match.' : 'No encontramos una coincidencia exacta.' }}</p>
                        @if ($search)
                            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500">
                                {{ app()->getLocale() === 'en' ? 'There are no exact results for “'.$search.'”, but we found products you may like.' : 'No hay resultados exactos para “'.$search.'”, pero encontramos productos que podrían interesarte.' }}
                            </p>
                        @else
                            <p class="mt-2 text-sm text-gray-500">{{ app()->getLocale() === 'en' ? 'Try removing a filter or explore the alternatives below.' : 'Prueba quitando algún filtro o explora las alternativas de abajo.' }}</p>
                        @endif
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
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

                @if ($similarProducts->isNotEmpty())
                    <section class="mt-10 border-t border-gray-200 pt-8" aria-labelledby="similar-products-title">
                        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[.18em] text-amber-800/75">{{ app()->getLocale() === 'en' ? 'Other options' : 'Otras opciones' }}</p>
                                <h2 id="similar-products-title" class="mt-1 text-xl font-bold tracking-tight text-gray-900 sm:text-2xl">
                                    {{ __('storefront.product.related') }}
                                </h2>
                                @if ($search)
                                    <p class="mt-1 text-sm text-gray-500">{{ app()->getLocale() === 'en' ? 'Similar alternatives to “'.$search.'”.' : 'Alternativas similares a “'.$search.'”.' }}</p>
                                @endif
                            </div>
                            <a href="{{ route('store.search') }}" class="text-sm font-semibold text-gray-700 transition hover:text-amber-800">
                                {{ app()->getLocale() === 'en' ? 'View all products' : 'Ver todo el catálogo' }} <span aria-hidden="true">↗</span>
                            </a>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
                            @foreach ($similarProducts as $product)
                                @include('store.components.product-card', ['product' => $product])
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        </div>
    </div>
</div>
