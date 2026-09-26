<div>
    <div class="mx-auto max-w-md px-4 py-16 sm:px-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
            <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-gray-100 text-gray-900">
                <flux:icon name="identification" class="size-7" />
            </span>
            <h1 class="mt-4 text-xl font-bold tracking-tight text-gray-900">Necesitas una cuenta</h1>
            <p class="mt-2 text-sm text-gray-600">
                Para completar tu compra inicia sesión o crea una cuenta gratuita.
            </p>
            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <a href="{{ route('login') }}"
                    class="inline-flex items-center justify-center rounded-full bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                    Iniciar sesión
                </a>
                <a href="{{ route('register') }}"
                    class="inline-flex items-center justify-center rounded-full border border-gray-300 px-6 py-3 text-sm font-semibold text-gray-900 transition hover:bg-gray-50">
                    Crear cuenta
                </a>
            </div>
        </div>
    </div>
</div>