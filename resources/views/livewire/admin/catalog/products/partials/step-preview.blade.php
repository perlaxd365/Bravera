<section x-show="$wire.currentStep === 6" x-cloak class="space-y-4" role="region" aria-label="Vista previa">
    <div>
        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-amber-700">Paso 6</p>
        <h4 class="mt-1 text-xl font-bold tracking-tight text-stone-950">Vista previa en la portada</h4>
        <p class="mt-1 text-sm leading-6 text-stone-600">Esta es la tarjeta real que verá el cliente en la página principal.</p>
    </div>

    @if (! $previewProduct)
        <div class="rounded-3xl border border-sky-200 bg-sky-50 p-6 text-center">
            <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-white text-sky-700 shadow-sm">
                <flux:icon name="eye" class="size-5" />
            </span>
            <p class="mt-4 text-sm font-semibold text-sky-950">Guarda primero el producto</p>
            <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-sky-800/80">
                Después de guardarlo podrás completar sus imágenes, variantes y proveedores y volver aquí para revisar la tarjeta final.
            </p>
            <button type="button" wire:click="goToStep(1)"
                class="mt-4 inline-flex min-h-10 items-center justify-center rounded-full bg-sky-900 px-4 text-sm font-semibold text-white">
                Volver al producto
            </button>
        </div>
    @else
        <div class="overflow-hidden rounded-[28px] border border-stone-200 bg-[#f8f6f1] shadow-sm">
            <div class="flex items-center justify-between gap-4 border-b border-stone-200 bg-white/85 px-5 py-4 backdrop-blur">
                <div class="flex items-center gap-3">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-amber-100 text-amber-900 ring-1 ring-inset ring-amber-200">
                        <flux:icon name="eye" class="size-4" />
                    </span>
                    <div>
                        <p class="text-sm font-bold text-stone-950">Productos destacados</p>
                        <p class="text-xs text-stone-500">Así se verá dentro de la portada</p>
                    </div>
                </div>
                <span class="rounded-full border border-stone-200 bg-white px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-stone-500">
                    Vista previa
                </span>
            </div>

            <div class="px-5 py-8 sm:px-8 sm:py-10">
                <div class="mx-auto w-full max-w-[310px]">
                    <div class="home-product-item">
                        @include('store.components.product-card', [
                            'product' => $previewProduct,
                            'preview' => true,
                        ])
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t border-stone-200 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs leading-5 text-stone-500">
                    Los enlaces y favoritos están desactivados en esta vista previa.
                </p>
                @if ($previewProduct->status && $previewProduct->is_visible)
                    <a href="{{ route('store.product', ['slug' => $previewProduct->slug]) }}" target="_blank" rel="noopener"
                        class="inline-flex min-h-10 items-center justify-center gap-2 rounded-full bg-stone-950 px-4 text-sm font-semibold text-white hover:bg-amber-800">
                        Ver en la tienda <flux:icon name="arrow-top-right-on-square" class="size-4" />
                    </a>
                @else
                    <span class="text-xs font-medium text-amber-800">
                        El producto aún no es visible para los clientes.
                    </span>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-stone-200 bg-white p-4">
            <p class="text-xs leading-5 text-stone-500">
                Si cambias el nombre, precio, imagen, descuento, marca o etiquetas, vuelve a este paso para revisar el resultado actualizado.
            </p>
        </div>
    @endif
</section>
