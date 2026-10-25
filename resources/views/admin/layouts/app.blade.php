<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('admin.layouts.partials.head')

<body class="bg-gray-50 font-sans text-gray-900 antialiased" x-data="{ sidebarOpen: false }">

    <div id="admin-loading-indicator" class="pointer-events-none fixed inset-x-0 top-0 z-[100]" role="status" aria-live="polite" hidden>
        <div class="h-1 overflow-hidden bg-amber-100">
            <div class="h-full w-2/3 animate-pulse rounded-r-full bg-amber-500"></div>
        </div>
        <div class="fixed left-1/2 top-4 flex -translate-x-1/2 items-center gap-2.5 rounded-full border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 shadow-xl">
            <span class="size-4 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900" aria-hidden="true"></span>
            <span id="admin-loading-label">Cargando…</span>
        </div>
    </div>

    <div class="flex min-h-screen">

        <div x-show="sidebarOpen" x-transition.opacity.duration.150ms class="fixed inset-0 z-40 bg-gray-950/40 backdrop-blur-sm lg:hidden"
            @click="sidebarOpen = false"></div>

        @include('admin.layouts.partials.sidebar')

        <div class="flex min-w-0 flex-1 flex-col lg:pl-64">

            @include('admin.layouts.partials.navbar')

            <main class="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>

            @include('admin.layouts.partials.footer')

        </div>

    </div>

    @include('admin.layouts.partials.scripts')

    @stack('scripts')
    @livewireScripts
    @fluxScripts

    <flux:toast position="top right" />

    <script>
        document.addEventListener('livewire:init', () => {
            const loadingIndicator = document.getElementById('admin-loading-indicator');
            const loadingLabel = document.getElementById('admin-loading-label');
            let activeCommits = 0;
            let interactionTimer;
            const openDetails = new Map();
            const restoringDetails = new WeakSet();

            const clearInteractionState = () => {
                clearTimeout(interactionTimer);

                document.querySelectorAll('[data-admin-loading]').forEach((control) => {
                    control.removeAttribute('data-admin-loading');
                    control.removeAttribute('aria-busy');
                    control.classList.remove('pointer-events-none', 'opacity-60', 'cursor-wait');
                });
            };

            const hideWhenIdle = () => {
                if (activeCommits === 0) {
                    loadingIndicator.hidden = true;
                    clearInteractionState();
                }
            };

            const showLoading = (control = null, label = 'Cargando…') => {
                clearTimeout(interactionTimer);
                loadingLabel.textContent = label;
                loadingIndicator.hidden = false;

                if (control) {
                    control.setAttribute('data-admin-loading', 'true');
                    control.setAttribute('aria-busy', 'true');
                    control.classList.add('pointer-events-none', 'opacity-60', 'cursor-wait');
                }

                interactionTimer = setTimeout(() => {
                    if (activeCommits === 0) {
                        hideWhenIdle();
                    }
                }, 1500);
            };

            const detailsKey = (details) => {
                const component = details.closest('[wire\\:id]');

                if (!component) {
                    return null;
                }

                const path = [];
                let node = details;

                while (node && node !== component) {
                    const parent = node.parentElement;

                    if (!parent) {
                        break;
                    }

                    path.unshift(Array.prototype.indexOf.call(parent.children, node));
                    node = parent;
                }

                return `${component.getAttribute('wire:id')}:${path.join('.')}`;
            };

            const rememberDetails = (details) => {
                const key = detailsKey(details);

                if (key) {
                    openDetails.set(key, details.open);
                }
            };

            const restoreDetails = (details) => {
                const key = detailsKey(details);

                if (!key || !openDetails.has(key) || details.open === openDetails.get(key)) {
                    return;
                }

                restoringDetails.add(details);
                details.open = openDetails.get(key);

                setTimeout(() => restoringDetails.delete(details), 0);
            };

            document.addEventListener('toggle', (event) => {
                if (event.target instanceof HTMLDetailsElement && !restoringDetails.has(event.target)) {
                    rememberDetails(event.target);
                }
            }, true);

            Livewire.hook('morph.updating', ({ el }) => {
                if (el instanceof HTMLDetailsElement) {
                    rememberDetails(el);
                }
            });

            Livewire.hook('morph.updated', ({ el }) => {
                if (el instanceof HTMLDetailsElement) {
                    restoreDetails(el);
                }
            });

            Livewire.hook('commit', ({ succeed, fail }) => {
                activeCommits++;
                showLoading(null, 'Cargando…');

                const finish = () => {
                    activeCommits = Math.max(0, activeCommits - 1);
                    hideWhenIdle();
                };

                succeed(finish);
                fail(finish);
            });

            document.addEventListener('click', (event) => {
                const target = event.target instanceof Element ? event.target : null;
                const control = target?.closest(
                    'button, a[href], [role="button"], input[type="submit"], input[type="button"]',
                );

                if (!control || control.matches(':disabled, [aria-disabled="true"]')) {
                    return;
                }

                const label = control.tagName === 'A' ? 'Abriendo…' : 'Procesando…';
                showLoading(control, label);
            }, true);

            document.addEventListener('submit', (event) => {
                const submitter = event.submitter;
                showLoading(submitter instanceof HTMLElement ? submitter : null, 'Guardando…');
            }, true);

            document.addEventListener('change', (event) => {
                const target = event.target instanceof Element ? event.target : null;
                const control = target?.closest('select');

                if (control && !control.matches(':disabled')) {
                    showLoading(control, 'Actualizando…');
                }
            }, true);

            window.addEventListener('pageshow', hideWhenIdle);

            Livewire.on('notify', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                const variant = {
                    error: 'danger',
                    danger: 'danger',
                    warning: 'warning',
                    info: 'info',
                    success: 'success',
                }[data.type] ?? 'neutral';

                window.Flux.toast(data.message, {
                    variant,
                    duration: 3000,
                });
            });
        });
    </script>
</body>

</html>
