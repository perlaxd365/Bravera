<section x-show="$wire.currentStep === 4" x-cloak class="space-y-4" role="region" aria-label="Proveedores">
    <div>
        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-amber-700">Paso 4</p>
        <h4 class="mt-1 text-xl font-bold tracking-tight text-stone-950">Proveedores y stock</h4>
        <p class="mt-1 text-sm leading-6 text-stone-600">Asigna inventario propio o de un proveedor externo a cada variante.</p>
    </div>

    @if (! $form->id)
        <div class="rounded-3xl border border-sky-200 bg-sky-50 p-6 text-center">
            <p class="text-sm font-semibold text-sky-950">Guarda primero el producto</p>
            <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-sky-800/80">Necesitas una variante antes de registrar sus proveedores.</p>
            <button type="button" wire:click="goToStep(1)" class="mt-4 inline-flex min-h-10 items-center justify-center rounded-full bg-sky-900 px-4 text-sm font-semibold text-white">Volver al producto</button>
        </div>
    @elseif ($variants->isEmpty())
        <div class="rounded-3xl border border-dashed border-amber-300 bg-amber-50/60 px-6 py-10 text-center">
            <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-white text-amber-800 shadow-sm"><flux:icon name="swatch" class="size-5" /></span>
            <p class="mt-4 text-sm font-semibold text-stone-950">Primero agrega una variante</p>
            <p class="mt-1 text-sm text-stone-600">El proveedor y su stock se guardan por variante.</p>
            <button type="button" wire:click="goToStep(3)" class="mt-4 inline-flex min-h-10 items-center justify-center rounded-full bg-stone-950 px-4 text-sm font-semibold text-white">Ir a variantes</button>
        </div>
    @else
        @if ($suppliers->isEmpty())
            <div class="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 sm:flex-row sm:items-center sm:justify-between">
                <p>No hay proveedores activos. Crea uno antes de asignar inventario externo.</p>
                <a href="{{ route('admin.catalog.suppliers.index') }}" class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-full bg-amber-950 px-4 text-xs font-semibold text-white">Registrar proveedor</a>
            </div>
        @endif

        <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-sm font-bold text-stone-950">Elige la variante</p>
                    <p class="mt-0.5 text-xs text-stone-500">Solo se mostrarán los proveedores de la variante seleccionada.</p>
                </div>
                @if ($supplierVariantVariantId)
                    <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200">Variante seleccionada</span>
                @endif
            </div>
            <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
                @foreach ($variants as $variant)
                    <button type="button" wire:click="createSupplierVariant({{ $variant->id }})"
                        @class([
                            'min-h-10 shrink-0 rounded-full border px-4 text-xs font-semibold transition',
                            'border-stone-950 bg-stone-950 text-white' => $supplierVariantVariantId === $variant->id,
                            'border-stone-300 bg-white text-stone-700 hover:bg-stone-50' => $supplierVariantVariantId !== $variant->id,
                        ])>
                        {{ $variant->sku }}
                    </button>
                @endforeach
            </div>
        </div>

        @if ($supplierVariantVariantId)
            <details class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm" open>
                <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 px-5 py-4">
                    <span class="flex items-center gap-3">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-sky-50 text-sky-700"><flux:icon name="truck" class="size-4" /></span>
                        <span>
                            <span class="block text-sm font-bold text-stone-950">{{ $supplierVariantForm->id ? 'Editar proveedor' : 'Agregar proveedor' }}</span>
                            <span class="mt-0.5 block text-xs text-stone-500">Costo, stock y tiempo de despacho.</span>
                        </span>
                    </span>
                    <flux:icon name="chevron-down" class="size-4 shrink-0 text-stone-400" />
                </summary>

                <div class="space-y-3 border-t border-stone-100 bg-stone-50/60 p-4 sm:p-5">
                    <details class="overflow-hidden rounded-xl border border-stone-200 bg-white" open>
                        <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 px-4 py-3">
                            <span>
                                <span class="block text-sm font-semibold text-stone-900">Origen y referencia</span>
                                <span class="mt-0.5 block text-xs text-stone-500">Proveedor, SKU y enlace del artículo.</span>
                            </span>
                            <flux:icon name="chevron-down" class="size-4 text-stone-400" />
                        </summary>
                        <div class="grid gap-4 border-t border-stone-100 p-4 sm:grid-cols-2">
                            <div>
                                <x-select label="Proveedor" wire:model.live="supplierVariantForm.supplier_id">
                                    <option value="">Selecciona un proveedor</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">
                                            {{ $supplier->is_internal ? 'Vendido por Brevare — inventario propio' : ($supplier->trade_name ?: $supplier->business_name) }}
                                        </option>
                                    @endforeach
                                </x-select>
                                @if ($suppliers->isEmpty())
                                    <a href="{{ route('admin.catalog.suppliers.index') }}" class="mt-1 inline-flex text-xs font-semibold text-amber-800 hover:text-amber-950">Ir a proveedores →</a>
                                @endif
                            </div>
                            <x-input label="SKU del proveedor" wire:model.blur="supplierVariantForm.supplier_sku" />
                            <div class="sm:col-span-2">
                                <x-input label="URL del producto en el proveedor" type="url" placeholder="https://proveedor.com/producto"
                                    wire:model.blur="supplierVariantForm.supplier_product_url" />
                            </div>
                        </div>
                    </details>

                    <details class="overflow-hidden rounded-xl border border-stone-200 bg-white">
                        <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 px-4 py-3">
                            <span>
                                <span class="block text-sm font-semibold text-stone-900">Costos e inventario</span>
                                <span class="mt-0.5 block text-xs text-stone-500">Valores internos para margen y disponibilidad.</span>
                            </span>
                            <flux:icon name="chevron-down" class="size-4 text-stone-400" />
                        </summary>
                        <div class="space-y-4 border-t border-stone-100 p-4">
                            <div class="grid gap-3 sm:grid-cols-3">
                                <x-input label="Costo" type="number" step="0.01" min="0" wire:model.blur="supplierVariantForm.cost_price" />
                                <x-input label="Costo de envío" type="number" step="0.01" min="0" wire:model.blur="supplierVariantForm.shipping_cost" />
                                <x-input label="Precio del proveedor" type="number" step="0.01" min="0" wire:model.blur="supplierVariantForm.supplier_sale_price" />
                            </div>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <x-input label="Stock" type="number" min="0" wire:model.blur="supplierVariantForm.stock" />
                                <x-input label="Stock reservado" type="number" min="0" wire:model.blur="supplierVariantForm.reserved_stock" />
                                <x-input label="Stock mínimo" type="number" min="0" wire:model.blur="supplierVariantForm.minimum_stock" />
                            </div>
                        </div>
                    </details>

                    <details class="overflow-hidden rounded-xl border border-stone-200 bg-white">
                        <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 px-4 py-3">
                            <span>
                                <span class="block text-sm font-semibold text-stone-900">Despacho y notas</span>
                                <span class="mt-0.5 block text-xs text-stone-500">Plazo, prioridad y observaciones internas.</span>
                            </span>
                            <flux:icon name="chevron-down" class="size-4 text-stone-400" />
                        </summary>
                        <div class="space-y-4 border-t border-stone-100 p-4">
                            <div class="grid gap-3 sm:grid-cols-3">
                                <x-input label="Días de despacho" type="number" min="0" wire:model.blur="supplierVariantForm.estimated_dispatch_days" />
                                <label class="flex min-h-12 cursor-pointer items-center gap-2 self-end rounded-xl border border-stone-200 px-3 text-sm text-stone-700">
                                    <input type="checkbox" wire:model="supplierVariantForm.is_default" class="size-4 rounded border-stone-300 text-stone-950 focus:ring-stone-900/20">
                                    Proveedor principal
                                </label>
                                <label class="flex min-h-12 cursor-pointer items-center gap-2 self-end rounded-xl border border-stone-200 px-3 text-sm text-stone-700">
                                    <input type="checkbox" wire:model="supplierVariantForm.is_active" class="size-4 rounded border-stone-300 text-stone-950 focus:ring-stone-900/20">
                                    Proveedor activo
                                </label>
                            </div>
                            <x-textarea label="Notas internas" rows="3" wire:model.blur="supplierVariantForm.internal_notes" />
                        </div>
                    </details>
                </div>

                <div class="flex flex-wrap justify-end gap-2 border-t border-stone-200 bg-white px-4 py-3">
                    <button type="button" wire:click="createSupplierVariant({{ $supplierVariantVariantId }})"
                        class="inline-flex min-h-10 items-center justify-center rounded-full border border-stone-300 px-4 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                        Limpiar
                    </button>
                    <button type="button" wire:click="saveSupplierVariant"
                        class="inline-flex min-h-10 items-center justify-center rounded-full bg-stone-950 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-stone-800">
                        {{ $supplierVariantForm->id ? 'Actualizar proveedor' : 'Guardar proveedor' }}
                    </button>
                </div>
            </details>

            <div>
                <h5 class="mb-3 text-sm font-bold text-stone-950">Proveedores de esta variante</h5>
                @forelse ($supplierVariants as $supplierVariant)
                    <article wire:key="supplier-variant-{{ $supplierVariant->id }}" class="mb-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h6 class="text-sm font-bold text-stone-950">{{ $supplierVariant->supplier->trade_name ?: $supplierVariant->supplier->business_name }}</h6>
                                    @if ($supplierVariant->is_default)
                                        <x-brevare.badge color="primary">Principal</x-brevare.badge>
                                    @endif
                                    @if ($supplierVariant->is_active)
                                        <x-brevare.badge color="success">Activo</x-brevare.badge>
                                    @else
                                        <x-brevare.badge color="neutral">Inactivo</x-brevare.badge>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-stone-500">{{ $supplierVariant->supplier_sku ?: 'Sin SKU de proveedor' }}</p>
                                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-stone-600">
                                    <span>Costo: <strong class="text-stone-950">S/ {{ number_format((float) $supplierVariant->cost_price, 2) }}</strong></span>
                                    <span>Stock: <strong class="text-stone-950">{{ $supplierVariant->stock }}</strong></span>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" wire:click="editSupplierVariant({{ $supplierVariant->id }})" class="inline-flex min-h-9 items-center gap-1 rounded-full border border-stone-300 px-3 text-xs font-semibold text-stone-700 hover:bg-stone-50">
                                    <flux:icon name="pencil" class="size-3.5" /> Editar
                                </button>
                                <button type="button" wire:click="toggleSupplierVariantStatus({{ $supplierVariant->id }})" class="min-h-9 rounded-full border border-stone-300 px-3 text-xs font-semibold text-stone-700 hover:bg-stone-50">
                                    {{ $supplierVariant->is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                                <button type="button" wire:click="deleteSupplierVariant({{ $supplierVariant->id }})" wire:confirm="¿Deseas eliminar este proveedor de la variante?" class="flex size-9 items-center justify-center rounded-full border border-red-200 text-red-600 hover:bg-red-50" aria-label="Eliminar proveedor">
                                    <flux:icon name="trash" class="size-4" />
                                </button>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-stone-300 bg-white px-5 py-8 text-center text-sm text-stone-500">
                        Esta variante todavía no tiene proveedores.
                    </div>
                @endforelse
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-stone-300 bg-white px-5 py-8 text-center">
                <p class="text-sm font-semibold text-stone-950">Selecciona una variante para continuar</p>
                <p class="mt-1 text-sm text-stone-500">Arriba encontrarás todas las variantes de este producto.</p>
            </div>
        @endif
    @endif
</section>
