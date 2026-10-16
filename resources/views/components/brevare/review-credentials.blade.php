@php
    $reviewLogin = config('brevare.review_login');
@endphp

@if ($reviewLogin['enabled'] ?? false)
    <div {{ $attributes->merge(['class' => 'auth-rise rounded-3xl border-2 border-amber-400 bg-amber-50 p-5 text-center shadow-xl shadow-amber-500/10 sm:p-6']) }}>
        <p class="text-base font-extrabold uppercase tracking-wide text-amber-900 sm:text-lg">
            ¿Vas a hacer pruebas de compra?
        </p>

        <p class="mx-auto mt-2 max-w-2xl text-sm leading-relaxed text-amber-900 sm:text-base">
            Entra con esta cuenta de prueba para revisar el proceso de compra completo
            <strong>sin restricciones</strong>.
        </p>

        <dl class="mx-auto mt-4 flex max-w-xl flex-wrap items-center justify-center gap-x-8 gap-y-2 rounded-2xl bg-white px-4 py-3 text-center shadow-sm sm:px-5 sm:py-4">
            <div class="flex items-baseline gap-2">
                <dt class="text-xs font-bold uppercase tracking-wider text-gray-500">Usuario</dt>
                <dd class="select-all break-all font-mono text-base font-bold text-gray-900 sm:text-lg">
                    {{ $reviewLogin['email'] }}
                </dd>
            </div>

            <div class="flex items-baseline gap-2">
                <dt class="text-xs font-bold uppercase tracking-wider text-gray-500">Contraseña</dt>
                <dd class="select-all break-all font-mono text-base font-bold text-gray-900 sm:text-lg">
                    {{ $reviewLogin['password'] }}
                </dd>
            </div>
        </dl>
    </div>
@endif
