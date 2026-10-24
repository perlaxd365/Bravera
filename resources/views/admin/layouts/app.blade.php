<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('admin.layouts.partials.head')

<body class="bg-gray-50 font-sans text-gray-900 antialiased" x-data="{ sidebarOpen: false }">

    <div id="admin-loading-indicator" class="pointer-events-none fixed inset-x-0 top-0 z-[100]" role="status" aria-live="polite" hidden>
        <div class="h-1 overflow-hidden bg-amber-100">
            <div class="h-full w-2/3 animate-pulse rounded-r-full bg-amber-500"></div>
        </div>
        <div class="fixed bottom-5 right-5 flex items-center gap-2.5 rounded-full border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 shadow-lg">
            <span class="size-4 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900" aria-hidden="true"></span>
            Cargando…
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
            let activeCommits = 0;

            Livewire.hook('commit', ({ succeed, fail }) => {
                activeCommits++;
                loadingIndicator.hidden = false;

                const finish = () => {
                    activeCommits = Math.max(0, activeCommits - 1);
                    loadingIndicator.hidden = activeCommits === 0;
                };

                succeed(finish);
                fail(finish);
            });

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
