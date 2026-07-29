<!DOCTYPE html>
<html lang="es">

<head>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="bg-light">

    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand">
                Bravera Admin
            </span>

            <div class="text-white">
                {{ auth()->user()->name }}
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">

        {{ $slot }}

    </div>

    @livewireScripts

</body>

</html>
