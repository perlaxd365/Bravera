<div id="brevare-cookie-consent"
    class="fixed inset-x-4 bottom-40 z-[60] hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-2xl shadow-gray-950/15 sm:bottom-6 sm:left-auto sm:right-6 sm:max-w-md sm:p-5"
    role="dialog"
    aria-live="polite"
    aria-label="Preferencias de cookies">
    <p class="text-sm font-semibold text-gray-950">Tu privacidad importa</p>
    <p class="mt-2 text-sm leading-6 text-gray-600">
        Usamos cookies necesarias para que la tienda funcione y, con tu permiso, cookies de medición y publicidad
        para mejorar la experiencia y medir nuestras campañas.
        <a href="{{ route('privacy') }}" class="font-medium text-gray-900 underline underline-offset-4">Política de privacidad</a>.
    </p>
    <div class="mt-4 flex flex-col gap-2 sm:flex-row">
        <button type="button" data-consent="all"
            class="inline-flex min-h-10 items-center justify-center rounded-full bg-gray-950 px-4 text-sm font-semibold text-white transition hover:bg-amber-800">
            Aceptar todas
        </button>
        <button type="button" data-consent="necessary"
            class="inline-flex min-h-10 items-center justify-center rounded-full border border-gray-300 px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
            Solo necesarias
        </button>
    </div>
</div>

<script>
    (() => {
        const key = @json(config('meta.consent_cookie', 'brevare_consent'));
        const banner = document.getElementById('brevare-cookie-consent');

        const get = () => {
            const match = document.cookie.match(new RegExp('(?:^|; )' + key + '=([^;]+)'));

            return match ? decodeURIComponent(match[1]) : null;
        };

        const set = (value) => {
            const secure = window.location.protocol === 'https:' ? '; Secure' : '';
            document.cookie = `${key}=${value}; Max-Age=15552000; Path=/; SameSite=Lax${secure}`;

            if (value !== 'all') {
                document.cookie = `brevare_visitor=; Max-Age=0; Path=/; SameSite=Lax${secure}`;
            }

            try {
                window.localStorage.setItem(key, value);
            } catch (error) {
                // El consentimiento sigue guardado en la cookie.
            }

            banner?.classList.add('hidden');
            window.dispatchEvent(new CustomEvent('brevare-consent-changed', {
                detail: { consent: value },
            }));
        };

        if (!get()) {
            banner?.classList.remove('hidden');
        }

        banner?.querySelectorAll('[data-consent]').forEach((button) => {
            button.addEventListener('click', () => set(button.dataset.consent));
        });

        window.BrevareConsent = {
            open: () => banner?.classList.remove('hidden'),
            get,
            set,
        };
    })();
</script>
