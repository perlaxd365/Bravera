<section x-show="$wire.currentStep === 5" x-cloak class="space-y-4" role="region" aria-label="Marketing">
    <div>
        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-amber-700">Paso 5</p>
        <h4 class="mt-1 text-xl font-bold tracking-tight text-stone-950">Publicidad en Meta</h4>
        <p class="mt-1 text-sm leading-6 text-stone-600">Crea y activa un anuncio usando la foto o el video del producto.</p>
    </div>

    @if (! $form->id)
        <div class="rounded-3xl border border-sky-200 bg-sky-50 p-6 text-center">
            <p class="text-sm font-semibold text-sky-950">Guarda primero el producto</p>
            <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-sky-800/80">Después podrás preparar su anuncio sin cerrar el asistente.</p>
            <button type="button" wire:click="goToStep(1)" class="mt-4 inline-flex min-h-10 items-center justify-center rounded-full bg-sky-900 px-4 text-sm font-semibold text-white">Volver al producto</button>
        </div>
    @elseif (! $metaAdsConfigured)
        <div class="overflow-hidden rounded-3xl border border-amber-200 bg-white shadow-sm">
            <div class="bg-amber-50 px-5 py-6 sm:px-7">
                <span class="flex size-11 items-center justify-center rounded-2xl bg-white text-amber-800 shadow-sm"><flux:icon name="key" class="size-5" /></span>
                <h5 class="mt-4 text-lg font-bold text-stone-950">Meta Ads todavía no está configurado.</h5>
                <p class="mt-1 max-w-xl text-sm leading-6 text-amber-900/80">Agrega el ID de cuenta, la página y el token de Marketing API desde Configuración. Los secretos se guardan cifrados.</p>
                <a href="{{ route('admin.settings.integrations', ['group' => 'meta_ads']) }}"
                    class="mt-5 inline-flex min-h-11 items-center gap-2 rounded-full bg-amber-950 px-5 text-sm font-semibold text-white hover:bg-stone-950">
                    <flux:icon name="cog-6-tooth" class="size-4" /> Configurar Meta Ads
                </a>
            </div>
            <div class="border-t border-amber-100 px-5 py-4 text-xs leading-5 text-stone-500 sm:px-7">
                Mientras no esté configurado, el resto de la ficha funciona normalmente. Este paso puede completarse después.
            </div>
        </div>
    @else
        @if (! $metaAdsReady)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-sm font-semibold text-amber-950">El producto aún no está listo para anunciarse</p>
                <p class="mt-1 text-sm leading-6 text-amber-900/80">Debe estar activo, visible, con stock y con una imagen principal.</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" wire:click="goToStep(1)" class="min-h-9 rounded-full border border-amber-300 bg-white px-3 text-xs font-semibold text-amber-900">Revisar publicación</button>
                    <button type="button" wire:click="goToStep(3)" class="min-h-9 rounded-full border border-amber-300 bg-white px-3 text-xs font-semibold text-amber-900">Revisar variantes e imágenes</button>
                </div>
            </div>
        @endif

        <details class="overflow-hidden rounded-2xl border border-sky-200 bg-white shadow-sm" @if ($metaAds->isNotEmpty()) open @endif>
            <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 px-5 py-4">
                <span class="flex items-center gap-3">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-sky-50 text-sky-700"><flux:icon name="megaphone" class="size-4" /></span>
                    <span>
                        <span class="flex flex-wrap items-center gap-2 text-sm font-bold text-stone-950">
                            Preparar anuncio
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Activo</span>
                        </span>
                        <span class="mt-0.5 block text-xs text-stone-500">Campaña, creatividad y texto.</span>
                    </span>
                </span>
                <flux:icon name="chevron-down" class="size-4 shrink-0 text-stone-400" />
            </summary>

            <div class="space-y-4 border-t border-sky-100 p-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-select label="Campaña existente" wire:model.live="metaCampaignId">
                            <option value="">Selecciona una campaña</option>
                            @foreach ($metaCampaigns as $campaign)
                                <option value="{{ $campaign['id'] }}">
                                    {{ $campaign['name'] }}@if ($campaign['status']) · {{ $campaign['status'] }}@endif
                                </option>
                            @endforeach
                        </x-select>
                        <button type="button" wire:click="loadMetaCampaigns"
                            wire:loading.attr="disabled" wire:target="loadMetaCampaigns"
                            class="-mt-2 mb-4 inline-flex min-h-9 items-center gap-1.5 text-xs font-semibold text-sky-700 transition hover:text-sky-900 disabled:opacity-50">
                            <flux:icon name="arrow-path" class="size-3.5" />
                            Recargar campañas
                        </button>
                    </div>
                    <x-select label="Conjunto de anuncios" wire:model.live="metaAdSetId" :disabled="! $metaCampaignId">
                        <option value="">Selecciona un conjunto</option>
                        @foreach ($metaAdSets as $adSet)
                            <option value="{{ $adSet['id'] }}">
                                {{ $adSet['name'] }}@if ($adSet['status']) · {{ $adSet['status'] }}@endif
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="mb-2 text-sm font-medium text-stone-800">Creatividad</p>
                        <div class="space-y-2">
                            <label class="flex min-h-14 cursor-pointer items-start gap-3 rounded-2xl border border-stone-200 p-3 text-sm text-stone-700">
                                <input type="radio" wire:model.live="metaMediaType" value="image"
                                    @disabled(! $metaImage)
                                    class="mt-0.5 size-4 border-stone-300 text-stone-950 focus:ring-stone-900/20">
                                <span>
                                    <span class="block font-semibold text-stone-900">Imagen principal</span>
                                    <span class="block text-xs text-stone-500">Usa la foto principal del producto.</span>
                                </span>
                            </label>
                            <label class="flex min-h-14 cursor-pointer items-start gap-3 rounded-2xl border border-stone-200 p-3 text-sm text-stone-700">
                                <input type="radio" wire:model.live="metaMediaType" value="video"
                                    @disabled(! $metaVideo)
                                    class="mt-0.5 size-4 border-stone-300 text-stone-950 focus:ring-stone-900/20">
                                <span>
                                    <span class="block font-semibold text-stone-900">Video del producto</span>
                                    <span class="block text-xs text-stone-500">Usa el video actual de la ficha.</span>
                                </span>
                            </label>
                        </div>
                        @if ($metaMediaType === 'video' && $metaVideo)
                            <p class="mt-2 text-xs leading-5 text-amber-700">
                                {{ $metaAdVideoUrl ? 'Se usará el video de anuncio en alta resolución.' : 'Se usará el video optimizado de la ficha. Puedes subir una versión en alta resolución abajo.' }}
                            </p>
                        @endif
                    </div>

                    <div class="flex min-h-36 items-center justify-center overflow-hidden rounded-2xl border border-stone-200 bg-stone-50 p-2">
                        @if ($metaMediaType === 'video' && $metaVideo)
                            <video src="{{ $metaVideo }}" controls preload="metadata" class="aspect-video w-full rounded-xl bg-stone-950 object-contain"></video>
                        @elseif ($metaImage)
                            <img src="{{ $metaImage }}" alt="{{ $product?->name }}" class="aspect-video w-full rounded-xl object-cover">
                        @else
                            <button type="button" wire:click="goToStep(3)" class="px-4 py-8 text-center text-sm text-stone-500">Sin imagen disponible. Agregar imágenes →</button>
                        @endif
                    </div>
                </div>

                <div class="rounded-2xl border border-dashed border-sky-300 bg-sky-50/40 p-4"
                    x-data="{ uploading: false, progress: 0, uploadError: null }"
                    x-on:livewire-upload-start.window="uploading = true; progress = 0; uploadError = null"
                    x-on:livewire-upload-progress.window="progress = Math.max(0, Math.min(100, Number($event.detail.progress || 0)))"
                    x-on:livewire-upload-finish.window="uploading = false; progress = 100"
                    x-on:livewire-upload-error.window="uploading = false; uploadError = 'No se pudo transferir el video. Inténtalo nuevamente.'">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-stone-900">Video exclusivo para el anuncio</p>
                            <p class="mt-1 text-xs leading-5 text-stone-500">Opcional. MP4, MOV o WebM hasta 50 MB; conserva alta resolución.</p>
                        </div>
                        @if ($metaAdVideoUrl)
                            <button type="button" wire:click="deleteMetaAdVideo" wire:confirm="¿Quitar el video exclusivo del anuncio?"
                                class="inline-flex min-h-9 shrink-0 items-center gap-1 rounded-full border border-red-200 px-3 text-xs font-semibold text-red-700 hover:bg-red-50">
                                <flux:icon name="trash" class="size-3.5" /> Quitar
                            </button>
                        @endif
                    </div>
                    <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <input type="file" wire:model="metaAdVideo" accept="video/mp4,video/quicktime,video/webm,.mp4,.mov,.webm"
                            x-bind:disabled="uploading"
                            wire:loading.attr="disabled" wire:target="metaAdVideo"
                            class="block min-w-0 flex-1 cursor-pointer rounded-xl border border-stone-300 bg-white text-sm text-stone-700 file:mr-4 file:rounded-l-xl file:border-0 file:bg-sky-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-sky-900 hover:file:bg-sky-200">
                        <button type="button" wire:click="saveMetaAdVideo"
                            wire:loading.attr="disabled" wire:target="metaAdVideo,saveMetaAdVideo"
                            @disabled(! $metaAdVideo)
                            class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-full bg-sky-700 px-4 text-sm font-semibold text-white transition hover:bg-sky-800 disabled:cursor-not-allowed disabled:bg-stone-300">
                            <span wire:loading.remove wire:target="saveMetaAdVideo">Guardar video</span>
                            <span wire:loading wire:target="saveMetaAdVideo" class="inline-flex items-center gap-2">
                                <span class="size-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span>
                                Guardando en Cloudinary…
                            </span>
                        </button>
                    </div>

                    <div x-show="uploading" x-cloak class="mt-3" role="status" aria-live="polite">
                        <div class="flex items-center justify-between gap-3 text-xs font-semibold text-sky-900">
                            <span>Subiendo video…</span>
                            <span x-text="`${progress}%`">0%</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-sky-100">
                            <div class="h-full rounded-full bg-sky-600 transition-[width] duration-200"
                                x-bind:style="`width: ${progress}%`"></div>
                        </div>
                    </div>

                    <p x-show="! uploading && progress === 100 && ! uploadError" x-cloak
                        class="mt-2 text-xs font-medium text-emerald-700" role="status">
                        Video preparado. Ahora pulsa «Guardar video».
                    </p>

                    <p x-show="uploadError" x-text="uploadError" x-cloak
                        class="mt-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700" role="alert"></p>
                    @error('metaAdVideo') <p class="mt-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700" role="alert">{{ $message }}</p> @enderror
                    @if ($metaAdVideo)
                        <p class="mt-2 truncate text-xs text-stone-500">Seleccionado: {{ $metaAdVideo->getClientOriginalName() }}</p>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-textarea label="Texto principal" rows="3" maxlength="2000" wire:model.blur="metaPrimaryText" />
                    <div class="space-y-0">
                        <x-input label="Titular" maxlength="40" wire:model.blur="metaHeadline" />
                        <x-input label="Descripción" maxlength="200" wire:model.blur="metaDescription" />
                        <x-select label="Botón" wire:model="metaCta">
                            <option value="SHOP_NOW">Comprar ahora</option>
                            <option value="LEARN_MORE">Más información</option>
                            <option value="SIGN_UP">Registrarse</option>
                            <option value="CONTACT_US">Contactar</option>
                        </x-select>
                    </div>
                </div>

                <x-input label="URL de destino" type="url" wire:model.blur="metaDestinationUrl" />

                @if ($metaAdsError)
                    <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">{{ $metaAdsError }}</div>
                @endif

                <div class="flex flex-col gap-3 rounded-2xl border border-sky-100 bg-sky-50/60 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="max-w-lg text-xs leading-5 text-sky-900">El anuncio se crea activo y usará el presupuesto configurado en el conjunto de anuncios seleccionado.</p>
                    <button type="button" wire:click="createMetaAd"
                        wire:loading.attr="disabled" wire:target="createMetaAd"
                        @disabled(! $metaAdsReady || ! $metaCampaignId || ! $metaAdSetId)
                        class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-full bg-sky-700 px-5 text-sm font-semibold text-white transition hover:bg-sky-800 disabled:cursor-not-allowed disabled:bg-stone-300">
                        <flux:icon name="megaphone" class="size-4" />
                        <span wire:loading.remove wire:target="createMetaAd">Crear y activar</span>
                        <span wire:loading wire:target="createMetaAd">Creando…</span>
                    </button>
                </div>
            </div>
        </details>

        @if ($metaAds->isNotEmpty())
            <div>
                <h5 class="mb-3 text-sm font-bold text-stone-950">Anuncios creados</h5>
                <div class="space-y-3">
                    @foreach ($metaAds as $metaAd)
                        <article wire:key="meta-ad-{{ $metaAd->id }}" class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-semibold text-stone-950">{{ $metaAd->ad_set_name ?: $metaAd->ad_set_id }}</p>
                                        <x-brevare.badge :color="$metaAd->statusColor()">{{ $metaAd->statusLabel() }}</x-brevare.badge>
                                    </div>
                                    <p class="mt-1 text-xs text-stone-500">{{ ucfirst($metaAd->media_type) }} · ID {{ $metaAd->ad_id ?: 'pendiente' }}</p>
                                    @if ($metaAd->error_message)
                                        <p class="mt-2 rounded-lg bg-red-50 px-2.5 py-2 text-xs leading-5 text-red-700">{{ $metaAd->error_message }}</p>
                                    @endif
                                </div>
                                @if ($metaAd->ad_id)
                                    <button type="button" wire:click="refreshMetaAd({{ $metaAd->id }})"
                                        wire:loading.attr="disabled" wire:target="refreshMetaAd"
                                        class="inline-flex min-h-9 items-center gap-1 rounded-full border border-stone-300 px-3 text-xs font-semibold text-stone-700 hover:bg-stone-50">
                                        <flux:icon name="arrow-path" class="size-3.5" /> Actualizar
                                    </button>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</section>
