<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@livewireStyles
@include('admin.layouts.partials.head')

<body class="admin-body">

    <div class="admin-shell">

        @include('admin.layouts.partials.sidebar')

        <div class="admin-main">

            @include('admin.layouts.partials.navbar')

            <main class="admin-content">
                {{ $slot }}
            </main>

            @include('admin.layouts.partials.footer')

        </div>

    </div>

    @include('admin.layouts.partials.scripts')

    @stack('scripts')
    @livewireScripts

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('livewire:init', () => {

            Livewire.on('notify', (event) => {

                const data = Array.isArray(event) ? event[0] : event;

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: data.type,
                    title: data.message,
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true
                });

            });

        });
    </script>
</body>

</html>
