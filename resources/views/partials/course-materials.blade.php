@if ($order->payment_status->value === 'paid')
    @php
        $courseItems = $order->items
            ->filter(fn ($item) => $item->isActive() && $item->product?->isCourse());
    @endphp

    @if ($courseItems->isNotEmpty())
        <section class="overflow-hidden rounded-2xl border border-sky-200 bg-white shadow-sm">
            <header class="border-b border-sky-100 bg-sky-50/70 px-5 py-4 sm:px-6">
                <div class="flex items-start gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-sky-700 shadow-sm">
                        <flux:icon name="document-arrow-down" class="size-5" />
                    </span>
                    <div>
                        <h2 class="font-bold text-sky-950">Materiales de tus cursos</h2>
                        <p class="mt-0.5 text-xs leading-5 text-sky-800">
                            Descarga los archivos o abre los enlaces incluidos en tu compra.
                        </p>
                    </div>
                </div>
            </header>

            <div class="divide-y divide-sky-100">
                @foreach ($courseItems as $item)
                    <article class="px-5 py-4 sm:px-6">
                        <p class="text-sm font-bold text-stone-950">{{ $item->product_name }}</p>

                        @if ($item->product?->courseAssets->isEmpty())
                            <p class="mt-2 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                El acceso está confirmado. Los materiales se publicarán pronto.
                            </p>
                        @else
                            <ul class="mt-3 space-y-2">
                                @foreach ($item->product->courseAssets as $asset)
                                    <li>
                                        <a href="{{ route('course.assets.open', ['order' => $order, 'asset' => $asset]) }}"
                                            @if (! $asset->isFile()) target="_blank" rel="noopener" @endif
                                            class="flex min-h-12 items-center justify-between gap-3 rounded-xl border border-stone-200 bg-stone-50/60 px-4 py-3 transition hover:border-sky-300 hover:bg-sky-50">
                                            <span class="flex min-w-0 items-center gap-3">
                                                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-white text-sky-700 shadow-sm">
                                                    <flux:icon :name="$asset->isFile() ? 'arrow-down-tray' : 'arrow-top-right-on-square'" class="size-4" />
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block truncate text-sm font-semibold text-stone-900">{{ $asset->title }}</span>
                                                    <span class="block text-[11px] text-stone-500">
                                                        {{ $asset->isFile() ? ($asset->file_name.' · '.$asset->sizeLabel()) : 'Abrir enlace del curso' }}
                                                    </span>
                                                </span>
                                            </span>
                                            <span class="shrink-0 text-xs font-semibold text-sky-700">
                                                {{ $asset->isFile() ? 'Descargar' : 'Abrir' }}
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif
@endif
