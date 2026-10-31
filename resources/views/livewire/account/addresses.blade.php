<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-10 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                <flux:icon name="map-pin" class="size-5" />
            </span>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-gray-900">{{ app()->getLocale() === 'en' ? 'My addresses' : 'Mis direcciones' }}</h1>
                <p class="text-sm text-gray-500">{{ app()->getLocale() === 'en' ? 'Manage where we send your orders.' : 'Gestiona los lugares a donde enviamos tus pedidos.' }}</p>
            </div>
        </div>
        @if (!$showForm)
            <button wire:click="newAddress"
                class="inline-flex items-center gap-1.5 rounded-full bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                <flux:icon name="plus" class="size-4" /> {{ app()->getLocale() === 'en' ? 'New address' : 'Nueva dirección' }}
            </button>
        @endif
    </div>

        @if ($addresses->isEmpty() && !$showForm)
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white py-16 text-center">
                <flux:icon name="map-pin" class="mx-auto mb-3 size-12 text-gray-300" />
                <p class="text-sm font-medium text-gray-700">{{ app()->getLocale() === 'en' ? 'You have no saved addresses.' : 'No tienes direcciones registradas.' }}</p>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($addresses as $address)
                <div class="flex flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" wire:key="addr-{{ $address->id }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h2 class="truncate font-bold text-gray-900">{{ $address->full_name }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ $address->address }}</p>
                            @if ($address->reference)
                                <p class="text-sm text-gray-500">Ref: {{ $address->reference }}</p>
                            @endif
                            <p class="text-sm text-gray-500">{{ $address->locationLabel() }}</p>
                            <p class="mt-1 text-sm font-medium text-gray-700">{{ $address->phone }}</p>
                            @if ($address->is_default)
                                <span class="mt-2 inline-block rounded-full bg-gray-900 px-3 py-1 text-xs font-semibold text-white">{{ app()->getLocale() === 'en' ? 'Primary address' : 'Dirección principal' }}</span>
                            @else
                                <button wire:click="makeDefault({{ $address->id }})"
                                    class="mt-2 text-sm font-medium text-gray-700 underline-offset-2 transition hover:text-gray-900 hover:underline">
                                    {{ app()->getLocale() === 'en' ? 'Set as primary' : 'Establecer como principal' }}
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2 border-t border-gray-100 pt-4">
                        <button wire:click="editAddress({{ $address->id }})"
                            class="inline-flex items-center gap-1.5 rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-gray-900 transition hover:bg-gray-50">
                            <flux:icon name="pencil" class="size-3.5" /> {{ app()->getLocale() === 'en' ? 'Edit' : 'Editar' }}
                        </button>
                        <button wire:click="deleteAddress({{ $address->id }})"
                            wire:confirm="{{ app()->getLocale() === 'en' ? 'Delete this address?' : '¿Eliminar esta dirección?' }}"
                            class="inline-flex items-center gap-1.5 rounded-full border border-red-300 px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50">
                            <flux:icon name="trash" class="size-3.5" /> {{ app()->getLocale() === 'en' ? 'Delete' : 'Eliminar' }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($showForm)
            <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-5 font-bold text-gray-900">
                    {{ $editing
                        ? (app()->getLocale() === 'en' ? 'Edit address' : 'Editar dirección')
                        : (app()->getLocale() === 'en' ? 'New address' : 'Nueva dirección') }}
                </h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">{{ app()->getLocale() === 'en' ? 'Full name' : 'Nombre completo' }}</label>
                        <input type="text" wire:model="fullName"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                        @error('fullName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">{{ app()->getLocale() === 'en' ? 'Phone' : 'Teléfono' }}</label>
                        <input type="text" wire:model="phone"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                        @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">{{ app()->getLocale() === 'en' ? 'Region' : 'Departamento' }}</label>
                        <div class="relative">
                            <select wire:model.live="departmentId"
                                class="w-full appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                <option value="">{{ app()->getLocale() === 'en' ? 'Select' : 'Seleccionar' }}</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept['id'] }}">{{ $dept['name'] }}</option>
                                @endforeach
                            </select>
                            <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">{{ app()->getLocale() === 'en' ? 'Province' : 'Provincia' }}</label>
                        <div class="relative">
                            <select wire:model.live="provinceId"
                                class="w-full appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                <option value="">{{ app()->getLocale() === 'en' ? 'Select' : 'Seleccionar' }}</option>
                                @foreach ($provinces as $prov)
                                    <option value="{{ $prov['id'] }}">{{ $prov['name'] }}</option>
                                @endforeach
                            </select>
                            <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">{{ app()->getLocale() === 'en' ? 'District' : 'Distrito' }}</label>
                        <div class="relative">
                            <select wire:model.live="districtId"
                                class="w-full appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 pr-9 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                                <option value="">{{ app()->getLocale() === 'en' ? 'Select' : 'Seleccionar' }}</option>
                                @foreach ($districts as $dist)
                                    <option value="{{ $dist['id'] }}">{{ $dist['name'] }}</option>
                                @endforeach
                            </select>
                            <flux:icon name="chevron-down" variant="mini" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                        </div>
                        @error('districtId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">{{ app()->getLocale() === 'en' ? 'Address' : 'Dirección' }}</label>
                        <input type="text" wire:model="address" placeholder="{{ app()->getLocale() === 'en' ? 'Street and number' : 'Av., Calle, Jr. y número' }}"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                        @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700">{{ app()->getLocale() === 'en' ? 'Reference' : 'Referencia' }}</label>
                        <input type="text" wire:model="reference" placeholder="{{ app()->getLocale() === 'en' ? 'Optional' : 'Opcional' }}"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" id="isDefault" wire:model="isDefault"
                                class="size-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900/30">
                            {{ app()->getLocale() === 'en' ? 'Set as primary address' : 'Establecer como dirección principal' }}
                        </label>
                    </div>
                    <div class="flex gap-2 sm:col-span-2">
                        <button wire:click="save"
                            class="rounded-full bg-gray-900 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                            {{ app()->getLocale() === 'en' ? 'Save' : 'Guardar' }}
                        </button>
                        <button wire:click="cancelForm"
                            class="rounded-full border border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                            {{ app()->getLocale() === 'en' ? 'Cancel' : 'Cancelar' }}
                        </button>
                    </div>
                </div>
            </div>
        @endif
</div>
