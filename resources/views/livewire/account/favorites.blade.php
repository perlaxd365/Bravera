<div class="space-y-6">
    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-[.16em] text-rose-600">{{ app()->getLocale() === 'en' ? 'Your picks' : 'Tu selección' }}</p>
        <h2 class="mt-2 text-2xl font-bold tracking-tight text-gray-950">{{ app()->getLocale() === 'en' ? 'Favorite products' : 'Productos favoritos' }}</h2>
        <p class="mt-1 text-sm text-gray-500">
            {{ app()->getLocale() === 'en' ? 'Save what you love and find it here whenever you want to see it again.' : 'Guarda lo que te gusta y encuéntralo aquí cuando quieras volver a verlo.' }}
        </p>
    </section>

    @if ($products->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
            <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-rose-50 text-rose-500">
                <flux:icon name="heart" class="size-7" />
            </span>
            <h3 class="mt-4 text-lg font-bold text-gray-900">{{ app()->getLocale() === 'en' ? 'No favorites yet' : 'Aún no tienes favoritos' }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ app()->getLocale() === 'en' ? 'Tap the heart on a product to save it here.' : 'Toca el corazón de un producto para guardarlo aquí.' }}</p>
            <a href="{{ route('store.search') }}"
                class="mt-5 inline-flex min-h-11 items-center justify-center rounded-full bg-gray-950 px-6 text-sm font-semibold text-white transition hover:bg-amber-800">
                {{ __('storefront.footer.explore') }}
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($products as $product)
                <div>@include('store.components.product-card', ['product' => $product])</div>
            @endforeach
        </div>

        <div>{{ $products->links() }}</div>
    @endif
</div>
