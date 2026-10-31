<div class="mx-auto max-w-7xl">
    <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Conexiones</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-950 sm:text-3xl">Integraciones y credenciales</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600">Actualiza URLs públicas y claves privadas por servicio. Los secretos se guardan cifrados y nunca se muestran completos.</p>
        </div>
        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-stone-950 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-900 disabled:cursor-wait disabled:opacity-60">
            <span wire:loading.remove wire:target="save">Guardar sección</span>
            <span wire:loading wire:target="save">Guardando…</span>
        </button>
    </header>

    <x-admin.settings-nav />

    <div class="grid gap-5 xl:grid-cols-[260px_minmax(0,1fr)]">
        <aside class="xl:sticky xl:top-24 xl:self-start">
            <div class="flex gap-2 overflow-x-auto pb-1 xl:grid xl:overflow-visible xl:pb-0">
                @foreach ($groups as $key => $group)
                    <button type="button" wire:click="setGroup('{{ $key }}')"
                        @class([
                            'flex min-h-16 min-w-52 shrink-0 items-center gap-3 rounded-2xl border px-4 text-left transition xl:min-w-0',
                            'border-stone-950 bg-stone-950 text-white shadow-sm' => $activeGroup === $key,
                            'border-stone-200 bg-white text-stone-700 hover:border-amber-300 hover:bg-amber-50/40' => $activeGroup !== $key,
                        ])>
                        <span @class(['flex size-9 shrink-0 items-center justify-center rounded-xl', 'bg-white/10 text-white' => $activeGroup === $key, 'bg-stone-100 text-stone-600' => $activeGroup !== $key])>
                            <flux:icon :name="$group['icon']" class="size-4" />
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold">{{ $group['label'] }}</span>
                            <span @class(['mt-0.5 block text-[11px]', 'text-white/60' => $activeGroup === $key, 'text-stone-500' => $activeGroup !== $key])>
                                {{ $summary[$key]['configured'] }}/{{ $summary[$key]['total'] }} listos
                            </span>
                        </span>
                    </button>
                @endforeach
            </div>
        </aside>

        <section class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
            <div class="border-b border-stone-100 bg-gradient-to-br from-amber-50/80 via-white to-white px-5 py-6 sm:px-7">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="max-w-2xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">{{ $groups[$activeGroup]['label'] }}</p>
                        <h2 class="mt-2 text-xl font-bold tracking-tight text-stone-950">Configura esta conexión</h2>
                        <p class="mt-1 text-sm leading-6 text-stone-600">{{ $groups[$activeGroup]['description'] }}</p>
                    </div>
                    <span @class(['rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset', 'bg-emerald-50 text-emerald-800 ring-emerald-200' => $summary[$activeGroup]['ready'], 'bg-amber-50 text-amber-900 ring-amber-200' => ! $summary[$activeGroup]['ready']])>
                        {{ $summary[$activeGroup]['ready'] ? 'Listo' : 'Faltan datos' }}
                    </span>
                </div>
            </div>

            @if ($activeGroup === 'meta_ads')
                <div class="border-b border-amber-100 bg-amber-50/60 px-5 py-5 sm:px-7">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="max-w-2xl">
                            <p class="text-sm font-bold text-amber-950">Antes de crear anuncios</p>
                            <p class="mt-1 text-sm leading-6 text-amber-900/80">
                                Confirma la app, la empresa, la cuenta publicitaria, la página y el token. Si Meta indica
                                “Autentica tu cuenta”, completa esa verificación en Ads Manager una sola vez.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if ($metaAdsLinks['app'])
                                <a href="{{ $metaAdsLinks['app'] }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex min-h-10 items-center justify-center rounded-full border border-amber-300 bg-white px-4 text-xs font-semibold text-amber-950 hover:bg-amber-100">
                                    Abrir aplicación
                                </a>
                            @endif
                            @if ($metaAdsLinks['business'])
                                <a href="{{ $metaAdsLinks['business'] }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex min-h-10 items-center justify-center rounded-full border border-amber-300 bg-white px-4 text-xs font-semibold text-amber-950 hover:bg-amber-100">
                                    Abrir empresa
                                </a>
                            @endif
                            @if ($metaAdsLinks['ads_manager'])
                                <a href="{{ $metaAdsLinks['ads_manager'] }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex min-h-10 items-center justify-center rounded-full bg-amber-950 px-4 text-xs font-semibold text-white hover:bg-stone-950">
                                    Autenticar en Ads Manager
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <form wire:submit="save" class="p-5 sm:p-7">
                <div class="grid gap-5 md:grid-cols-2">
                    @foreach ($fields as $field => $definition)
                        @if (($definition['type'] ?? 'text') === 'boolean')
                            <label class="flex min-h-20 cursor-pointer items-start justify-between gap-4 rounded-2xl border border-stone-200 bg-stone-50/70 p-4 md:col-span-2">
                                <span>
                                    <span class="block text-sm font-semibold text-stone-900">{{ $definition['label'] }}</span>
                                    <span class="mt-1 block text-xs leading-5 text-stone-500">{{ $definition['description'] }}</span>
                                </span>
                                <input type="checkbox" wire:model="values.{{ $field }}" class="mt-1 size-5 shrink-0 rounded border-stone-300 text-emerald-700 focus:ring-emerald-600/30">
                            </label>
                        @elseif (! empty($definition['secret']))
                            <label class="block">
                                <span class="flex items-center justify-between gap-3 text-sm font-medium text-stone-800">
                                    {{ $definition['label'] }}
                                    @if (! empty($definition['optional']))
                                        <span class="rounded-full bg-stone-100 px-2 py-0.5 text-[10px] font-semibold text-stone-500">Opcional</span>
                                    @endif
                                    @if ($configured[$field])
                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Configurado</span>
                                    @endif
                                </span>
                                <input type="password" autocomplete="new-password" wire:model="secrets.{{ $field }}"
                                    placeholder="{{ $configured[$field] ? 'Configurado · escribe solo para reemplazarlo' : 'Pega la credencial privada' }}"
                                    class="mt-2 min-h-11 w-full rounded-xl border-stone-300 bg-white text-sm text-stone-900 placeholder:text-stone-400 focus:border-emerald-600 focus:ring-emerald-600/20">
                                <span class="mt-1.5 block text-xs leading-5 text-stone-500">{{ $definition['description'] }}</span>
                                @error('secrets.'.$field) <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                        @else
                            <label class="block">
                                <span class="flex items-center gap-2 text-sm font-medium text-stone-800">
                                    {{ $definition['label'] }}
                                    @if (! empty($definition['optional']))
                                        <span class="rounded-full bg-stone-100 px-2 py-0.5 text-[10px] font-semibold text-stone-500">Opcional</span>
                                    @endif
                                </span>
                                <input type="{{ $definition['type'] === 'url' ? 'url' : 'text' }}"
                                    inputmode="{{ $definition['type'] === 'url' ? 'url' : 'text' }}"
                                    wire:model="values.{{ $field }}"
                                    placeholder="{{ $definition['placeholder'] ?? '' }}"
                                    class="mt-2 min-h-11 w-full rounded-xl border-stone-300 bg-white text-sm text-stone-900 placeholder:text-stone-400 focus:border-emerald-600 focus:ring-emerald-600/20">
                                <span class="mt-1.5 block text-xs leading-5 text-stone-500">{{ $definition['description'] }}</span>
                                @error('values.'.$field) <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                        @endif
                    @endforeach
                </div>

                <div class="mt-7 flex flex-col gap-3 border-t border-stone-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="max-w-xl text-xs leading-5 text-stone-500">
                        Al guardar una credencial nueva, reemplaza la anterior del panel. Si dejas un secreto vacío, se conserva el valor actual.
                    </p>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save"
                        class="inline-flex min-h-11 items-center justify-center rounded-full bg-stone-950 px-5 text-sm font-semibold text-white transition hover:bg-emerald-900 disabled:cursor-wait disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">Guardar {{ $groups[$activeGroup]['label'] }}</span>
                        <span wire:loading wire:target="save">Guardando…</span>
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
