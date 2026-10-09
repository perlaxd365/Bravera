@php($company = config('brevare.company'))
<div>
    <section class="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <nav class="mb-6 text-sm text-gray-400">
            <a href="{{ route('home') }}" class="transition hover:text-gray-900">Inicio</a>
            <span class="mx-2">/</span>
            <span class="text-gray-600">Libro de Reclamaciones</span>
        </nav>

        <header class="border-b border-gray-200 pb-6">
            <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">Libro de Reclamaciones</h1>
            <p class="mt-2 text-sm text-gray-500">
                Conforme al Código de Protección y Defensa del Consumidor (Ley N° 29571).
                Completa el formulario y te atenderemos a la brevedad.
            </p>
        </header>

        @if ($submittedCode)
            <div class="mt-8 rounded-2xl border border-emerald-200 bg-emerald-50 p-6">
                <h3 class="text-sm font-semibold text-emerald-800">¡Registro exitoso!</h3>
                <p class="mt-1 text-sm text-emerald-700">
                    Tu hoja de reclamación fue registrada con el código
                    <strong class="font-mono">{{ $submittedCode }}</strong>.
                    Recibirás respuesta en el correo indicado en un plazo máximo de 15 días hábiles.
                </p>
            </div>
        @endif

        <form wire:submit="submit" class="mt-8 space-y-10">
            <fieldset class="space-y-5">
                <legend class="text-lg font-semibold text-gray-900">1. Datos del consumidor</legend>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-brevare.floating-input wire:model="name" id="claim_name" label="Nombre completo" icon="user" required />
                    <x-brevare.floating-input wire:model="email" id="claim_email" label="Correo electrónico" type="email" icon="mail" required />
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <label for="claim_document_type" class="mb-1.5 block text-sm font-medium text-gray-700">Tipo de documento</label>
                        <select
                            wire:model="document_type"
                            id="claim_document_type"
                            class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-gray-900 focus:ring-gray-900/20"
                        >
                            <option value="DNI">DNI</option>
                            <option value="CE">Carné de extranjería</option>
                            <option value="RUC">RUC</option>
                            <option value="Pasaporte">Pasaporte</option>
                        </select>
                    </div>

                    <x-brevare.floating-input wire:model="document_number" id="claim_document_number" label="N° de documento" required />
                    <x-brevare.floating-input wire:model="phone" id="claim_phone" label="Teléfono" icon="phone" required />
                </div>

                <x-brevare.floating-input wire:model="address" id="claim_address" label="Domicilio" required />

                <label class="flex items-center gap-2.5 text-sm text-gray-600">
                    <input wire:model.live="is_minor" id="is_minor" type="checkbox"
                        class="size-4 rounded border-gray-300 text-gray-900 shadow-sm focus:ring-gray-900/20">
                    El consumidor es menor de edad
                </label>

                @if ($is_minor)
                    <x-brevare.floating-input wire:model="guardian_name" id="guardian_name" label="Nombre del padre, madre o apoderado" required />
                @endif
            </fieldset>

            <fieldset class="space-y-5">
                <legend class="text-lg font-semibold text-gray-900">2. Identificación del bien o servicio</legend>

                <x-brevare.floating-input wire:model="order_number" id="order_number" label="Número de pedido (opcional)" />

                <div>
                    <span class="mb-1.5 block text-sm font-medium text-gray-700">Tipo</span>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input wire:model="claim_type" type="radio" value="reclamo"
                                class="size-4 border-gray-300 text-gray-900 focus:ring-gray-900/20">
                            Reclamo
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input wire:model="claim_type" type="radio" value="queja"
                                class="size-4 border-gray-300 text-gray-900 focus:ring-gray-900/20">
                            Queja
                        </label>
                    </div>
                    @error('claim_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="claimed_good" class="mb-1.5 block text-sm font-medium text-gray-700">Bien o servicio materia del reclamo</label>
                    <textarea wire:model="claimed_good" id="claimed_good" rows="2"
                        class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-gray-900 focus:ring-gray-900/20"></textarea>
                    @error('claimed_good') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </fieldset>

            <fieldset class="space-y-5">
                <legend class="text-lg font-semibold text-gray-900">3. Detalle y pedido</legend>

                <div>
                    <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700">Detalle del reclamo o queja</label>
                    <textarea wire:model="description" id="description" rows="4"
                        class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-gray-900 focus:ring-gray-900/20"></textarea>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="request" class="mb-1.5 block text-sm font-medium text-gray-700">Pedido concreto</label>
                    <textarea wire:model="request" id="request" rows="3"
                        class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-gray-900 focus:ring-gray-900/20"></textarea>
                    @error('request') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </fieldset>

            <x-brevare.button type="submit" :block="false" class="w-full sm:w-auto">
                Enviar reclamación
            </x-brevare.button>
        </form>

        <div class="mt-12 rounded-2xl border border-gray-200 bg-gray-50 p-6">
            <h3 class="text-sm font-semibold text-gray-900">Datos de contacto</h3>
            <p class="mt-1 text-sm text-gray-600">
                Correo: <a href="mailto:{{ $company['support_email'] }}" class="font-medium text-gray-900 underline underline-offset-4">{{ $company['support_email'] }}</a> ·
                Teléfono: <a href="tel:{{ $company['phone_e164'] }}" class="font-medium text-gray-900 underline underline-offset-4">{{ $company['phone'] }}</a>
            </p>
            <p class="mt-1 text-sm text-gray-600">{{ $company['address'] }}, distrito de {{ $company['city'] }}, provincia y departamento de {{ $company['region'] }}, {{ $company['country_name'] }}.</p>
        </div>
    </section>
</div>
