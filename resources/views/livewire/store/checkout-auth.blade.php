<div>
    <div class="mx-auto max-w-md px-4 py-16 sm:px-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
            <div class="text-center">
                <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-amber-100 text-amber-900">
                    <flux:icon name="shopping-bag" class="size-7" />
                </span>
                <h1 class="mt-4 text-xl font-bold tracking-tight text-gray-900">{{ app()->getLocale() === 'en' ? 'Check out as a guest' : 'Compra como invitado' }}</h1>
                <p class="mt-2 text-sm text-gray-600">
                    {{ app()->getLocale() === 'en' ? 'Enter your details and pay without creating a password.' : 'Completa tus datos y paga sin crear una contraseña.' }}
                </p>
            </div>

            <form wire:submit="continueAsGuest" class="mt-6 space-y-4 text-left">
                <div>
                    <label for="guest-name" class="mb-1.5 block text-xs font-semibold text-gray-700">{{ app()->getLocale() === 'en' ? 'Full name' : 'Nombre completo' }}</label>
                    <input id="guest-name" type="text" autocomplete="name" wire:model="guestName"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                    @error('guestName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="guest-email" class="mb-1.5 block text-xs font-semibold text-gray-700">{{ app()->getLocale() === 'en' ? 'Email address' : 'Correo electrónico' }}</label>
                    <input id="guest-email" type="email" autocomplete="email" wire:model="guestEmail"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                    @error('guestEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="guest-phone" class="mb-1.5 block text-xs font-semibold text-gray-700">{{ app()->getLocale() === 'en' ? 'Phone' : 'Teléfono' }}</label>
                    <input id="guest-phone" type="tel" autocomplete="tel" wire:model="guestPhone"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10">
                    @error('guestPhone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex w-full items-center justify-center rounded-full bg-gray-950 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-800 disabled:cursor-wait disabled:opacity-70">
                    <span wire:loading.remove>{{ app()->getLocale() === 'en' ? 'Continue without an account' : 'Continuar sin crear cuenta' }}</span>
                    <span wire:loading>{{ app()->getLocale() === 'en' ? 'Preparing checkout…' : 'Preparando checkout…' }}</span>
                </button>
            </form>

            <div class="my-6 flex items-center gap-3 text-xs text-gray-400">
                <span class="h-px flex-1 bg-gray-200"></span>
                <span>o</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                <a href="{{ route('login') }}"
                    class="inline-flex items-center justify-center rounded-full border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-900 transition hover:bg-gray-50">
                    {{ __('storefront.common.sign_in') }}
                </a>
                <a href="{{ route('register') }}"
                    class="inline-flex items-center justify-center rounded-full px-5 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                    {{ __('storefront.common.create_account') }}
                </a>
            </div>

            <p class="mt-5 text-center text-xs leading-5 text-gray-500">
                {{ app()->getLocale() === 'en' ? 'We will use your email for the receipt and order updates. You can create a password later.' : 'Usaremos tu correo para el comprobante y los avisos de tu pedido. Podrás crear una contraseña después.' }}
            </p>
        </div>
    </div>
</div>
