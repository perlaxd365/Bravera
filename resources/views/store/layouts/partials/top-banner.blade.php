<div class="relative z-50 overflow-hidden bg-[#171714] text-white">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-x-3 gap-y-1 px-4 py-2 text-center text-[11px] font-semibold tracking-wide sm:text-xs">
        <span class="inline-flex items-center gap-2">
            <flux:icon name="truck" class="size-4 shrink-0 text-amber-300" />
            <span>{{ __('storefront.topbar.delivery') }}</span>
        </span>
        <span class="hidden text-white/35 sm:inline" aria-hidden="true">|</span>
        <span class="inline-flex items-center gap-2">
            <a href="{{ route('storefront.language', ['locale' => app()->getLocale() === 'en' ? 'es' : 'en']) }}"
                class="rounded px-1 py-0.5 underline decoration-white/30 underline-offset-2 transition hover:text-amber-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-300"
                aria-label="{{ __('storefront.preferences.language') }}: {{ app()->getLocale() === 'en' ? __('storefront.preferences.spanish') : __('storefront.preferences.english') }}">
                {{ app()->getLocale() === 'en' ? 'English' : 'Español' }}
            </a>
            <span class="text-white/35" aria-hidden="true">·</span>
            <a href="{{ route('storefront.currency', ['currency' => $storefront->currency === 'USD' ? 'PEN' : 'USD']) }}"
                class="rounded px-1 py-0.5 underline decoration-white/30 underline-offset-2 transition hover:text-amber-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-300"
                aria-label="{{ __('storefront.preferences.currency') }}: {{ $storefront->currency === 'USD' ? __('storefront.preferences.pen') : __('storefront.preferences.usd') }}">
                {{ $storefront->currency === 'USD' ? 'USD $' : 'PEN S/' }}
            </a>
        </span>
    </div>
</div>
