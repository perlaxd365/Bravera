<div>
    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data @keydown.escape.window="$wire.set('show', false)">
            <div class="absolute inset-0 bg-gray-950/40 backdrop-blur-sm" @click="$wire.set('show', false)"></div>

            <div class="relative w-full max-w-3xl rounded-2xl border border-gray-200 bg-white shadow-2xl shadow-gray-950/10">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-4">
                    <h3 class="text-base font-semibold tracking-tight text-gray-900">
                        {{ $form->id ? 'Editar Cupón' : 'Nuevo Cupón' }}
                    </h3>
                    <button wire:click="$set('show', false)" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Cerrar">
                        <flux:icon name="x-mark" variant="mini" class="size-5" />
                    </button>
                </div>

                <form wire:submit="save">
                    <div class="grid gap-3 px-6 py-5 sm:grid-cols-2">
                        <div>
                            <x-input label="Código" wire:model.live="form.code"
                                placeholder="BREVARE-XXXXXX" />

                            <div class="mt-1">
                                <a href="#" wire:click.prevent="form.generateCode"
                                    class="text-sm font-medium text-gray-700 transition hover:text-gray-900">
                                    Generar código
                                </a>
                            </div>
                        </div>

                        <div>
                            <x-input label="Nombre" wire:model.live="form.name"
                                placeholder="Ej: Bienvenida 10% offline" />
                        </div>

                        <div>
                            <x-select label="Tipo" wire:model.live="form.type">
                                @foreach (\App\Enums\CouponType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </x-select>
                        </div>

                        <div>
                            <x-input label="Valor {{ $form->type === 'percentage' ? '(%)' : '(S/)' }}"
                                :type="'number'"
                                step="0.01"
                                min="0.01"
                                wire:model.live="form.value" />
                        </div>

                        <div>
                            <x-input label="Descuento máximo (S/)" type="number" step="0.01" min="0"
                                wire:model.live="form.max_discount" placeholder="Opcional" />
                        </div>

                        <div>
                            <x-input label="Subtotal mínimo (S/)" type="number" step="0.01" min="0"
                                wire:model.live="form.min_subtotal" placeholder="0.00" />
                        </div>

                        <div>
                            <x-input label="Límite de usos" type="number" min="1"
                                wire:model.live="form.usage_limit" placeholder="Opcional" />
                        </div>

                        <div>
                            <x-input label="Límite por usuario" type="number" min="1"
                                wire:model.live="form.per_user_limit" placeholder="1" />
                        </div>

                        <div>
                            <x-input label="Válido desde" type="date"
                                wire:model.live="form.starts_at" />
                        </div>

                        <div>
                            <x-input label="Válido hasta" type="date"
                                wire:model.live="form.ends_at" />
                        </div>

                        <div>
                            <x-select label="Aplicar a" wire:model.live="form.applies_to">
                                @foreach (\App\Enums\CouponAppliesTo::cases() as $applies)
                                    <option value="{{ $applies->value }}">{{ $applies->label() }}</option>
                                @endforeach
                            </x-select>
                        </div>

                        <div>
                            <x-select label="Elemento" wire:model.live="form.applies_to_id"
                                :disabled="$form->applies_to === 'all'">
                                <option value="">Todas</option>
                                @if ($form->applies_to === 'supplier')
                                    @foreach ($applyOptions['suppliers'] as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                @elseif ($form->applies_to === 'product')
                                    @foreach ($applyOptions['products'] as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                @elseif ($form->applies_to === 'category')
                                    @foreach ($applyOptions['categories'] as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                @endif
                            </x-select>
                        </div>

                        <label class="flex cursor-pointer items-center gap-2 pt-6 text-sm font-medium text-gray-700">
                            <input type="checkbox" id="couponActive" wire:model.live="form.is_active"
                                class="size-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900/30">
                            Cupón activo
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                        <button type="button" wire:click="$set('show', false)"
                            class="rounded-full border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-full bg-gray-900 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                            <flux:icon name="check" class="size-4" />
                            {{ $form->id ? 'Actualizar' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>