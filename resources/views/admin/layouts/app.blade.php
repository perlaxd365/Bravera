<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('admin.layouts.partials.head')

<body class="bg-gray-50 font-sans text-gray-900 antialiased" x-data="{ sidebarOpen: false }">

    <div class="flex min-h-screen">

        <div x-show="sidebarOpen" x-transition.opacity.duration.150ms class="fixed inset-0 z-40 bg-gray-950/40 backdrop-blur-sm lg:hidden"
            @click="sidebarOpen = false"></div>

        @include('admin.layouts.partials.sidebar')

        <div class="flex min-w-0 flex-1 flex-col lg:pl-64">

            @include('admin.layouts.partials.navbar')

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
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