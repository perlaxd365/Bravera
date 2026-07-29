<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@include('admin.layouts.partials.head')

<body class="admin-body">

    <div class="admin-shell">

        @include('admin.layouts.partials.sidebar')

        <div class="admin-main">

            @include('admin.layouts.partials.navbar')

            <main class="admin-content">
                @yield('content')
            </main>

            @include('admin.layouts.partials.footer')

        </div>

    </div>

    @include('admin.layouts.partials.scripts')

    @stack('scripts')

</body>

</html>
