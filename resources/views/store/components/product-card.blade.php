@php
    $preview = $preview ?? false;
    $coverImage = $product->coverImageModel()?->sizedUrl(900);
    $cardVariants = $product->availableVariants()->get([
        'id',
        'sale_price',
        'compare_price',
        'wholesale_price',
        'wholesale_min_quantity',
    ]);
    $minVariant = $cardVariants->sortBy('sale_price')->first();
    $minPrice = $minVariant ? (float) $minVariant->sale_price : 0;
    $maxPrice = (float) ($cardVariants->max('sale_price') ?? 0);
    $hasWholesale = $cardVariants->contains(fn ($variant) => $variant->hasWholesalePrice());
    $comparePrice = (float) ($minVariant?->compare_price ?? 0);
    $discountPercent = $comparePrice > $minPrice && $minPrice > 0
        ? (int) round((1 - ($minPrice / $comparePrice)) * 100)
        : 0;
    $filledReviewStars = min(5, max(0, (int) round((float) ($product->reviews_avg_rating ?? 0))));
    $favoriteIds = app(\App\Services\FavoriteService::class)->productIdsFor(auth()->id());
    $isFavorite = in_array($product->id, $favoriteIds, true);
    $availableColors = $product->variants
        ->filter(fn ($variant) => $variant->is_active && $variant->supplierVariants->contains(fn ($supplierVariant) => $supplierVariant->is_active && $supplierVariant->availableStock() > 0))
        ->flatMap(fn ($variant) => $variant->attributeValues
            ->filter(fn ($attributeValue) => $attributeValue->attribute?->slug === 'color')
            ->map(fn ($attributeValue) => [
                'name' => $attributeValue->value?->value,
                'hex' => $attributeValue->value?->color,
            ]))
        ->filter(fn ($color) => filled($color['name']) && preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', (string) $color['hex']))
        ->unique(fn ($color) => mb_strtolower($color['name']))
        ->values();
@endphp

<div class="relative">
    <a href="{{ $preview ? '#' : route('store.product', ['slug' => $product->slug]) }}"
        @if ($preview) @click.prevent tabindex="-1" aria-label="Vista previa de {{ $product->name }}" @endif
        class="group flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-amber-300 hover:shadow-xl hover:shadow-amber-950/10">
        <div class="product-card-media relative aspect-[4/3] max-h-[460px] overflow-hidden bg-[#f7f5ef]">
            @if ($coverImage)
                <div class="absolute inset-0 flex items-center justify-center p-4 sm:p-6" aria-hidden="true">
                    <flux:icon name="photo" class="size-12 text-gray-300" />
                </div>
                <img src="{{ $coverImage }}" alt="{{ $product->name }}" loading="lazy" decoding="async"
                    onerror="this.remove()"
                    class="relative z-10 h-full w-full object-contain transition-transform duration-500 group-hover:scale-[1.025]">
            @else
                <div class="flex h-full items-center justify-center text-gray-300">
                    <flux:icon name="photo" class="size-12" />
                </div>
            @endif
            @if ($discountPercent > 0)
                <span class="absolute left-3 top-3 rounded-full bg-rose-600 px-2.5 py-1 text-xs font-extrabold text-white shadow-md">-{{ $discountPercent }}%</span>
            @endif
            @if ($product->is_featured ?? false)
                <span class="absolute right-3 top-3 rounded-full bg-amber-300 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-950 shadow-sm">Destacado</span>
            @endif
            @if ($product->isCourse())
                <span class="absolute bottom-3 right-3 z-20 rounded-full bg-sky-700 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white shadow-sm">Curso digital</span>
            @endif
            @if ($product->isSoldByBrevare() || $hasWholesale)
                <div class="absolute bottom-3 left-3 z-20 flex max-w-[calc(100%-1.5rem)] flex-col items-start gap-1.5">
                    @if ($product->isSoldByBrevare())
                        <span class="rounded-full border border-white/70 bg-white/95 px-2.5 py-1 text-[9px] font-bold uppercase leading-4 tracking-wide text-gray-900 shadow-sm sm:px-3 sm:text-[10px]">Vendido por Brevare</span>
                    @endif
                    @if ($hasWholesale)
                        <span class="rounded-full bg-emerald-700 px-2.5 py-1 text-[9px] font-bold uppercase leading-4 tracking-wide text-white shadow-sm sm:px-3 sm:text-[10px]">Mayorista</span>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex flex-1 flex-col p-3.5 sm:p-4">
            <p class="text-[10px] font-bold uppercase tracking-[.12em] text-gray-600 sm:text-xs">
                {{ $product->brand?->name ?? $product->category?->name }}
            </p>
            <p class="mt-1 line-clamp-2 min-h-10 text-sm font-semibold leading-5 text-gray-900 group-hover:text-amber-950" title="{{ $product->name }}">
                {{ $product->name }}
            </p>

            <div class="mt-2 flex min-h-5 items-center gap-1.5" aria-label="{{ $product->reviews_count ?? 0 }} opiniones">
                @if (($product->reviews_count ?? 0) > 0)
                    <span class="text-sm leading-none tracking-[1px] text-amber-600" aria-label="{{ number_format((float) $product->reviews_avg_rating, 1) }} de 5 estrellas">{{ str_repeat('★', $filledReviewStars).str_repeat('☆', 5 - $filledReviewStars) }}</span>
                    <span class="text-xs font-semibold text-gray-700">{{ number_format((float) $product->reviews_avg_rating, 1) }}</span>
                    <span class="text-xs text-gray-600">({{ $product->reviews_count }})</span>
                @else
                    <span class="text-[11px] font-medium text-emerald-700">Disponible para comprar</span>
                @endif
            </div>

            @if ($availableColors->isNotEmpty())
                <div class="mt-2 flex min-h-5 items-center gap-1.5" aria-label="Colores disponibles: {{ $availableColors->pluck('name')->implode(', ') }}">
                    @foreach ($availableColors->take(5) as $color)
                        <span title="{{ $color['name'] }}" aria-hidden="true" class="size-4 rounded-full border border-white shadow-[0_0_0_1px_rgba(17,24,39,0.18)] transition group-hover:scale-110" style="background-color: {{ $color['hex'] }}"></span>
                    @endforeach
                    @if ($availableColors->count() > 5)
                        <span class="ml-0.5 text-[10px] font-medium text-gray-500">+{{ $availableColors->count() - 5 }}</span>
                    @endif
                    <span class="sr-only">{{ $availableColors->count() }} colores</span>
                </div>
            @endif

            <div class="mt-auto flex flex-wrap items-baseline gap-x-2 gap-y-0.5 pr-12 pt-3">
                <x-brevare.price :amount="$minPrice" size="sm" />
                @if ($discountPercent > 0)
                    <span class="text-xs text-gray-500 line-through">S/ {{ number_format($comparePrice, 2) }}</span>
                @endif
                @if ($maxPrice > $minPrice && $minPrice > 0)
                    <span class="w-full text-[11px] text-gray-500">Variantes hasta S/ {{ number_format($maxPrice, 2) }}</span>
                @endif
            </div>
        </div>
    </a>
    @if ($preview)
        <span aria-hidden="true"
            class="absolute bottom-4 right-4 z-30 flex size-10 items-center justify-center rounded-full border border-gray-200 bg-white/95 text-gray-600 shadow-sm backdrop-blur">
            <flux:icon name="heart" class="size-5" />
        </span>
    @elseif (auth()->check())
        <form method="POST" action="{{ route('favorites.toggle', $product) }}" class="absolute bottom-4 right-4 z-30">
            @csrf
            <button type="submit" aria-label="{{ $isFavorite ? 'Quitar de favoritos' : 'Guardar en favoritos' }}"
                class="flex size-10 items-center justify-center rounded-full border shadow-sm backdrop-blur transition {{ $isFavorite ? 'border-rose-200 bg-rose-600 text-white hover:bg-rose-700' : 'border-gray-200 bg-white/95 text-gray-600 hover:border-rose-200 hover:text-rose-600' }}">
                <flux:icon name="heart" class="size-5 {{ $isFavorite ? 'fill-current' : '' }}" />
            </button>
        </form>
    @else
        <a href="{{ route('login', ['redirect' => route('store.product', ['slug' => $product->slug])]) }}" aria-label="Inicia sesión para guardar favoritos"
            class="absolute bottom-4 right-4 z-30 flex size-10 items-center justify-center rounded-full border border-gray-200 bg-white/95 text-gray-600 shadow-sm backdrop-blur transition hover:border-rose-200 hover:text-rose-600">
            <flux:icon name="heart" class="size-5" />
        </a>
    @endauth
</div>
