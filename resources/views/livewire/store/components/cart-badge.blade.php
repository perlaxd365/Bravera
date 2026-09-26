<a href="{{ route('store.cart') }}" data-cart-badge class="relative flex items-center gap-2 rounded-full p-2.5 text-gray-600 transition hover:bg-gray-100 hover:text-gray-900" aria-label="Carrito de compras">
    <flux:icon name="shopping-cart" class="size-5" />
    @if ($count > 0)
        <span wire:key="cart-badge-{{ $count }}" class="animate-badge-pop absolute right-0.5 top-0.5 flex min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
            {{ $count > 99 ? '99+' : $count }}
            <span class="sr-only">artículos</span>
        </span>
    @endif
</a>