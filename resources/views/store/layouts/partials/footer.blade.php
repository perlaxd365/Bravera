<footer class="mt-auto border-t border-gray-200 bg-white">
    <div class="bg-amber-300">
        <div class="mx-auto grid max-w-7xl gap-3 px-4 py-4 text-sm font-semibold text-amber-950 sm:grid-cols-3 sm:px-6 lg:px-8">
            <p class="flex items-center gap-2"><flux:icon name="truck" class="size-5" /> {{ __('storefront.footer.shipping') }}</p>
            <p class="flex items-center gap-2"><flux:icon name="lock-closed" class="size-5" /> {{ __('storefront.footer.secure') }}</p>
            <p class="flex items-center gap-2"><flux:icon name="chat-bubble-left-right" class="size-5" /> {{ __('storefront.footer.support') }}</p>
        </div>
    </div>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-bold tracking-tight text-gray-900">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-amber-300 text-amber-950">
                        <flux:icon name="shopping-bag" class="size-4" />
                    </span>
                    {{ app()->getLocale() === 'en' ? 'Brevare' : 'Brevare Perú' }}
                </a>
                <p class="mt-3 max-w-sm text-sm leading-relaxed text-gray-500">
                    <span class="font-medium text-gray-700">{{ __('storefront.footer.tagline') }}</span><br>
                    {{ __('storefront.footer.description') }}
                </p>
                <a href="{{ route('store.search') }}" class="mt-5 inline-flex min-h-11 items-center gap-2 rounded-full bg-gray-950 px-5 text-sm font-semibold text-white transition hover:bg-amber-700">{{ __('storefront.footer.explore') }} <span aria-hidden="true">→</span></a>
                @php
                    $company = config('brevare.company');
                @endphp
                <ul class="mt-5 space-y-1 text-sm text-gray-500">
                    <li><a class="transition hover:text-gray-900" href="mailto:{{ $company['support_email'] }}">{{ $company['support_email'] }}</a></li>
                    <li><a class="transition hover:text-gray-900" href="tel:{{ $company['phone_e164'] }}">{{ $company['phone'] }}</a></li>
                    <li>{{ $company['address'] }}, {{ $company['city'] }} - {{ $company['country_name'] }}</li>
                </ul>
                @php
                    $socialLinks = collect(config('seo.social_links', []))
                        ->filter(fn ($network) => is_array($network)
                            && filled($network['url'] ?? null)
                            && filter_var($network['url'], FILTER_VALIDATE_URL)
                            && filled($network['network'] ?? null)
                            && filled($network['name'] ?? null))
                        ->values();
                @endphp
                @if ($socialLinks->isNotEmpty())
                    <div class="mt-6 border-t border-stone-200/80 pt-5">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <p class="text-[10px] font-bold uppercase tracking-[.18em] text-amber-900/75">{{ __('storefront.footer.follow') }}</p>
                            <p class="text-xs text-stone-500">{{ __('storefront.footer.social_copy') }}</p>
                        </div>
                        <ul class="mt-3 flex flex-wrap gap-2" aria-label="Redes sociales de Brevare">
                            @foreach ($socialLinks as $social)
                                <li>
                                    <a href="{{ $social['url'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="Seguir a Brevare en {{ $social['name'] }}"
                                        title="Brevare en {{ $social['name'] }}"
                                        class="group flex size-11 items-center justify-center rounded-full border border-stone-200 bg-stone-50 shadow-[0_1px_0_rgba(28,25,23,.04)] transition duration-300 hover:-translate-y-0.5 hover:border-amber-300 hover:bg-white hover:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700 motion-reduce:transform-none motion-reduce:transition-none">
                                        <img src="{{ asset('images/social/'.$social['network'].'.svg') }}"
                                            alt=""
                                            width="20"
                                            height="20"
                                            loading="lazy"
                                            decoding="async"
                                            class="size-5 grayscale transition duration-300 group-hover:scale-110 group-hover:grayscale-0 motion-reduce:transform-none motion-reduce:transition-none">
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div>
                <p class="text-sm font-semibold text-gray-900">{{ __('storefront.footer.shop') }}</p>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('home') }}">{{ __('storefront.common.home') }}</a></li>
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('store.search') }}">{{ __('storefront.common.catalog') }}</a></li>
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('store.cart') }}">{{ __('storefront.common.cart') }}</a></li>
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('about') }}">{{ __('storefront.common.about') }}</a></li>
                    @foreach (\App\Support\Seo\CatalogLinks::curated() as $category)
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('store.category', ['path' => $category->path]) }}">{{ $category->seo_label ?? $category->name }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <p class="text-sm font-semibold text-gray-900">{{ __('storefront.footer.account') }}</p>
                <ul class="mt-4 space-y-3 text-sm">
                    @auth
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('account.orders') }}">{{ __('storefront.common.my_orders') }}</a></li>
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('account.addresses') }}">{{ __('storefront.common.addresses') }}</a></li>
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('account.points') }}">{{ __('storefront.common.points') }}</a></li>
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('account.coupons') }}">{{ __('storefront.common.coupons') }}</a></li>
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('account.favorites') }}">{{ __('storefront.common.favorites') }}</a></li>
                    @else
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('login') }}">{{ __('storefront.common.sign_in') }}</a></li>
                        <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('register') }}">{{ __('storefront.common.create_account') }}</a></li>
                    @endauth
                </ul>
            </div>

            <div>
                <p class="text-sm font-semibold text-gray-900">{{ __('storefront.footer.help') }}</p>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('claims') }}">{{ __('storefront.footer.claims') }}</a></li>
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('returns') }}">{{ __('storefront.footer.returns') }}</a></li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-900">{{ __('storefront.footer.legal') }}</p>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer">{{ __('storefront.footer.terms') }}</a></li>
                    <li><a class="text-gray-500 transition hover:text-gray-900" href="{{ route('privacy') }}" target="_blank" rel="noopener noreferrer">{{ __('storefront.footer.privacy') }}</a></li>
                    <li><button type="button" onclick="window.BrevareConsent?.open()" class="text-gray-500 transition hover:text-gray-900">{{ __('storefront.footer.cookie_preferences') }}</button></li>
                </ul>
            </div>
        </div>

        <section class="mt-10 overflow-hidden rounded-[1.75rem] border border-stone-200 bg-[#faf8f3]" aria-label="{{ app()->getLocale() === 'en' ? 'Payment methods' : 'Métodos de pago' }}">
            <div class="grid gap-6 p-5 sm:p-7 lg:grid-cols-[.8fr_1.4fr] lg:items-center lg:p-8">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[.2em] text-amber-900">{{ app()->getLocale() === 'en' ? 'Protected purchase' : 'Compra protegida' }}</p>
                    <h3 class="mt-2 text-2xl font-semibold tracking-[-.035em] text-stone-950 sm:text-3xl">{{ __('storefront.footer.payment_title') }}</h3>
                    <p class="mt-2 max-w-md text-sm leading-6 text-stone-600">
                        {{ __('storefront.footer.payment_copy') }}
                    </p>
                    <p class="mt-4 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800">
                        <flux:icon name="lock-closed" class="size-3.5" /> {{ __('storefront.footer.payment_secure') }}
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <article class="rounded-2xl border border-stone-200 bg-white p-4 shadow-[0_1px_0_rgba(28,25,23,.04)]">
                        <div class="flex min-h-8 items-center">
                            <img src="https://culqi.com/assets/images/shared/logos/culqiConNombre.svg"
                                alt="Culqi"
                                width="92"
                                height="26"
                                loading="lazy"
                                decoding="async"
                                referrerpolicy="no-referrer"
                                class="h-6 w-auto max-w-24 object-contain">
                        </div>
                        <p class="mt-3 text-xs font-semibold text-stone-800">Tarjetas, Yape y Plin</p>
                        <ul class="mt-3 flex flex-wrap gap-1.5" aria-label="Medios de pago con Culqi">
                            @foreach ([
                                ['Visa', 'visa.svg'],
                                ['Mastercard', 'masterCard.svg'],
                                ['American Express', 'amex.svg'],
                                ['Diners Club', 'diners.svg'],
                                ['Yape', 'yape.svg'],
                                ['Plin', 'plin.svg'],
                            ] as [$method, $logo])
                                <li class="flex h-9 w-14 items-center justify-center rounded-lg border border-stone-200 bg-white px-1.5">
                                    <img src="https://culqi.com/assets/images/shared/logos/{{ $logo }}"
                                        alt="{{ $method }}"
                                        title="{{ $method }}"
                                        width="52"
                                        height="22"
                                        loading="lazy"
                                        decoding="async"
                                        referrerpolicy="no-referrer"
                                        class="max-h-5 w-auto max-w-full object-contain">
                                </li>
                            @endforeach
                        </ul>
                    </article>

                    <article class="rounded-2xl border border-stone-200 bg-white p-4 shadow-[0_1px_0_rgba(28,25,23,.04)]">
                        <div class="flex min-h-8 items-center gap-2">
                            <img src="https://cdn.simpleicons.org/mercadopago/00B1EA"
                                alt=""
                                width="26"
                                height="26"
                                loading="lazy"
                                decoding="async"
                                referrerpolicy="no-referrer"
                                class="size-6 object-contain">
                            <span class="text-[15px] font-bold tracking-tight text-stone-900">
                                <span>Mercado</span> <span>Pago</span>
                            </span>
                        </div>
                        <p class="mt-3 text-xs font-semibold text-stone-800">Tarjeta de crédito o débito</p>
                        <ul class="mt-3 flex flex-wrap gap-1.5" aria-label="Medios de pago con Mercado Pago">
                            @foreach ([
                                ['Visa', 'visa.svg'],
                                ['Mastercard', 'masterCard.svg'],
                                ['American Express', 'amex.svg'],
                                ['Diners Club', 'diners.svg'],
                            ] as [$method, $logo])
                                <li class="flex h-9 w-14 items-center justify-center rounded-lg border border-stone-200 bg-white px-1.5">
                                    <img src="https://culqi.com/assets/images/shared/logos/{{ $logo }}"
                                        alt="{{ $method }}"
                                        title="{{ $method }}"
                                        width="52"
                                        height="22"
                                        loading="lazy"
                                        decoding="async"
                                        referrerpolicy="no-referrer"
                                        class="max-h-5 w-auto max-w-full object-contain">
                                </li>
                            @endforeach
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        <div class="mt-8 flex items-center justify-center border-t border-gray-100 pt-6">
            <p class="text-center text-sm text-gray-500">&copy; {{ date('Y') }} {{ $company['legal_name'] }} · RUC {{ $company['ruc'] }}. {{ __('storefront.footer.rights') }}</p>
        </div>
        <div class="mt-4 flex flex-wrap items-center justify-center gap-3 text-xs font-semibold text-stone-500">
            <a href="{{ route('storefront.language', ['locale' => 'es']) }}" class="hover:text-stone-900">Español</a>
            <a href="{{ route('storefront.language', ['locale' => 'en']) }}" class="hover:text-stone-900">English</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('storefront.currency', ['currency' => 'PEN']) }}" class="hover:text-stone-900">PEN S/</a>
            <a href="{{ route('storefront.currency', ['currency' => 'USD']) }}" class="hover:text-stone-900">USD $</a>
        </div>
    </div>
</footer>
