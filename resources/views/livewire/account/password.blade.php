<div>
    <div class="space-y-6">
        <div class="flex items-center gap-3">
            <span class="flex size-10 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                <flux:icon name="lock" class="size-5" />
            </span>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-gray-900">Cambiar contraseña</h1>
                <p class="text-sm text-gray-500">
                    Para mantenerte protegido, primero verificamos tu identidad por correo.
                </p>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            {{-- Progreso --}}
            <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4">
                @php
                    $steps = ['Solicitar código', 'Verificar código', 'Nueva contraseña'];
                    $current = ['idle' => 0, 'code' => 1, 'new' => 2, 'done' => 3][$step] ?? 0;
                @endphp
                @foreach ($steps as $i => $label)
                    <div class="flex items-center gap-2">
                        @if ($i > 0)
                            <span class="h-px w-6 bg-gray-200 sm:w-10"></span>
                        @endif
                        <span class="flex items-center gap-1.5">
                            <span @class([
                                'flex size-6 items-center justify-center rounded-full text-xs font-bold',
                                'bg-gray-900 text-white' => $i < $current || ($i === $current && $i === 2),
                                'bg-gray-900 text-white ring-4 ring-gray-900/10' => $i === $current && $i !== 2,
                                'bg-gray-100 text-gray-400' => $i > $current,
                            ])>
                                @if ($i < $current || ($i === $current && $i === 2))
                                    <flux:icon name="check" class="size-3.5" />
                                @else
                                    {{ $i + 1 }}
                                @endif
                            </span>
                            <span @class([
                                'hidden text-xs font-medium sm:inline',
                                'text-gray-900' => $i <= $current,
                                'text-gray-400' => $i > $current,
                            ])>{{ $label }}</span>
                        </span>
                    </div>
                @endforeach
            </div>

            <div class="p-6 sm:p-8" x-data="{
                    seconds: @js($resendSeconds),
                    timer: null,
                    get canResend() { return this.seconds <= 0; },
                    tick() {
                        clearInterval(this.timer);
                        this.timer = setInterval(() => {
                            if (this.seconds > 0) {
                                this.seconds--;
                            } else {
                                clearInterval(this.timer);
                            }
                        }, 1000);
                    },
                    init() {
                        if (this.seconds > 0) {
                            this.tick();
                        }
                    },
                }" x-on:password-code-sent.window="seconds = 60; tick();">

                {{-- Paso 4: contraseña actualizada --}}
                @if ($step === 'done')
                    <div class="mx-auto max-w-md text-center">
                        <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <flux:icon name="check" class="size-7" />
                        </span>

                        <h2 class="mt-5 text-lg font-bold tracking-tight text-gray-900">
                            ¡Tu contraseña se actualizó correctamente!
                        </h2>
                        <p class="mt-2 text-sm leading-relaxed text-gray-500">
                            A partir de ahora usa tu nueva contraseña para iniciar sesión en Brevare.
                        </p>

                        <div class="mt-6 flex justify-center">
                            <x-brevare.button :block="false" wire:click="cancel">
                                Finalizar
                            </x-brevare.button>
                        </div>
                    </div>

                {{-- Paso 1: pedir el código --}}
                @elseif ($step === 'idle')
                    <div class="mx-auto max-w-md text-center">
                        <span class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-200">
                            <flux:icon name="mail" class="size-3.5" />
                            {{ auth()->user()->email }}
                        </span>

                        <h2 class="mt-5 text-lg font-bold tracking-tight text-gray-900">
                            {{ auth()->user()->needsPasswordSetup() ? 'Crea tu contraseña' : 'Confirma que eres tú' }}
                        </h2>
                        <p class="mt-2 text-sm leading-relaxed text-gray-500">
                            Enviaremos un código de 6 dígitos a tu correo. Tiene validez de 10 minutos, así que ingrésalo apenas lo recibas.
                        </p>

                        <div class="mt-6 flex flex-wrap justify-center gap-3">
                            <x-brevare.button :block="false" wire:click="requestCode" wire:target="requestCode" x-show="canResend">
                                Enviar código
                            </x-brevare.button>
                            <span x-show="!canResend" x-cloak
                                class="inline-flex items-center gap-2 rounded-xl bg-gray-100 px-5 py-3.5 text-sm font-medium text-gray-500">
                                <flux:icon name="clock" class="size-4" />
                                Reenviar en <span x-text="seconds"></span>s
                            </span>
                        </div>
                        @error('code')
                            <p class="mt-3 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                {{-- Paso 2: ingresar el código --}}
                @elseif ($step === 'code')
                    <div class="mx-auto max-w-md">
                        <label for="code" class="mb-1.5 block text-sm font-medium text-gray-700">Código de verificación</label>
                        <input id="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                            wire:model="code" placeholder="000000"
                            class="w-full rounded-xl border border-gray-300 bg-white px-3 py-3 text-center text-2xl font-bold tracking-[0.5em] text-gray-900 placeholder:text-gray-300 focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                        @error('code') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror

                        <p class="mt-3 flex items-center gap-1.5 text-xs text-gray-400">
                            <flux:icon name="mail" class="size-3.5" />
                            Lo enviamos a <span class="font-medium text-gray-500">{{ auth()->user()->email }}</span>
                        </p>

                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <x-brevare.button :block="false" wire:click="verifyCode" wire:target="verifyCode">
                                Verificar código
                            </x-brevare.button>
                            <button type="button" wire:click="requestCode" x-show="canResend"
                                class="inline-flex items-center gap-1.5 rounded-xl px-4 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-100">
                                <flux:icon name="arrow-path" class="size-4" /> Reenviar código
                            </button>
                            <span x-show="!canResend" x-cloak
                                class="inline-flex items-center gap-1.5 rounded-xl px-4 py-3 text-sm font-medium text-gray-500">
                                <flux:icon name="clock" class="size-4" /> Reenviar en <span x-text="seconds"></span>s
                            </span>
                            <button type="button" wire:click="cancel"
                                class="rounded-xl px-4 py-3 text-sm font-medium text-gray-500 transition hover:bg-gray-100">
                                Cancelar
                            </button>
                        </div>
                    </div>

                {{-- Paso 3: nueva contraseña --}}
                @else
                    <div class="mx-auto max-w-md space-y-5">
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            Identidad verificada. Ahora define tu nueva contraseña.
                        </div>

                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">Nueva contraseña</label>
                            <input id="password" type="password" wire:model="password"
                                placeholder="Mínimo 8 caracteres"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700">Confirmar contraseña</label>
                            <input id="password_confirmation" type="password" wire:model="password_confirmation"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                        </div>

                        <div class="flex flex-wrap items-center gap-3 pt-1">
                            <x-brevare.button :block="false" wire:click="updatePassword" wire:target="updatePassword">
                                Actualizar contraseña
                            </x-brevare.button>
                            <button type="button" wire:click="cancel"
                                class="rounded-xl px-4 py-3 text-sm font-medium text-gray-500 transition hover:bg-gray-100">
                                Cancelar
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>