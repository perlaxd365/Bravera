@php
    $minPrice = $product->minPrice();
    $maxPrice = $product->maxPrice();
@endphp

<div>
    <a href="{{ route('store.product', ['slug' => $product->slug]) }}"
        class="group flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white transition duration-200 hover:shadow-lg hover:shadow-gray-200/60 hover:border-gray-300">
        <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
            @if ($product->coverImage())
                <img src="{{ $product->coverImage() }}" alt="{{ $product->name }}" loading="lazy"
                    class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
            @else
                <div class="flex h-full items-center justify-center text-gray-300">
                    <flux:icon name="photo" class="size-12" />
                </div>
            @endif
        </div>

        <div class="flex flex-1 flex-col p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                {{ $product->brand?->name ?? $product->category?->name }}
            </p>
            <p class="mt-1 line-clamp-2 text-sm font-medium text-gray-900" title="{{ $product->name }}">
                {{ $product->name }}
            </p>

            <div class="mt-auto pt-3 flex items-baseline gap-2">
                <x-brevare.price :amount="$minPrice" size="sm" />
                @if ($maxPrice > $minPrice && $minPrice > 0)
                    <span class="text-xs text-gray-400">hasta S/ {{ number_format((float) $maxPrice, 2) }}</span>
                @endif
            </div>
        </div>
    </a>
</div>