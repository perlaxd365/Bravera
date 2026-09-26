<div class="space-y-6">
    {{-- Datos personales --}}
    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <header class="flex items-center gap-3 border-b border-gray-100 px-6 py-4">
            <span class="flex size-9 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                <flux:icon name="user" class="size-4.5" />
            </span>
            <div>
                <h2 class="font-bold text-gray-900">Datos personales</h2>
                <p class="text-xs text-gray-500">Actualiza la información de tu cuenta.</p>
            </div>
        </header>

        <form wire:submit="updateProfile" class="space-y-5 p-6">
            <div class="flex items-center gap-4">
                <div class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 font-bold text-gray-500 ring-1 ring-inset ring-gray-200">
                    @if ($avatar_file)
                        <img src="{{ $avatar_file->temporaryUrl() }}" alt="Avatar" class="size-14 object-cover" loading="lazy">
                    @elseif ($avatar)
                        <img src="{{ $avatar }}" alt="Avatar" class="size-14 object-cover" loading="lazy">
                    @else
                        {{ mb_strtoupper(mb_substr($name, 0, 1)) }}
                    @endif
                </div>
                <div class="text-sm text-gray-500">
                    <p class="font-medium text-gray-900">Tu avatar</p>
                    <p>Sube una imagen desde tu equipo (opcional).</p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700">Nombre y apellidos</label>
                    <input id="name" type="text" wire:model="name"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">Correo electrónico</label>
                    <input id="email" type="email" wire:model="email"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1.5 text-xs text-gray-400">Si cambias el correo, deberás verificar el nuevo.</p>
                </div>

                <div>
                    <label for="phone" class="mb-1.5 block text-sm font-medium text-gray-700">Teléfono</label>
                    <input id="phone" type="text" wire:model="phone"
                        placeholder="+51 999 999 999"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="avatar_file" class="mb-1.5 block text-sm font-medium text-gray-700">Avatar</label>
                    <input id="avatar_file" type="file" wire:model="avatar_file" accept="image/*"
                        class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-full file:border-0 file:bg-gray-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-gray-800">
                    <p class="mt-1.5 text-xs text-gray-400">JPG, PNG o WebP. Máximo 5 MB.</p>
                    @error('avatar') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @error('avatar_file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end border-t border-gray-100 pt-5">
                <x-bravera.button type="submit" :block="false" wire:target="updateProfile">
                    Guardar cambios
                </x-bravera.button>
            </div>
        </form>
    </section>

    {{-- Contraseña --}}
    <section class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="flex size-9 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                <flux:icon name="lock" class="size-4.5" />
            </span>
            <div>
                <h2 class="font-bold text-gray-900">Contraseña</h2>
                <p class="text-xs text-gray-500">
                    {{ auth()->user()->needsPasswordSetup() ? 'Aún no tienes contraseña definida.' : 'Cambia tu contraseña cuando lo necesites.' }}
                    Para protegerte, verificamos tu identidad con un código por correo.
                </p>
            </div>
        </div>
        <a href="{{ route('account.password') }}" wire:navigate
            class="inline-flex items-center gap-1.5 rounded-full bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
            {{ auth()->user()->needsPasswordSetup() ? 'Crear contraseña' : 'Cambiar contraseña' }}
            <flux:icon name="arrow-right" class="size-4" />
        </a>
    </section>
</div>