<aside class="admin-sidebar" id="adminSidebar">

    <a class="brand" href="{{ route('admin.dashboard') }}">
        <span class="brand-mark">B</span>
        <span>Bravera</span>
    </a>

    <p class="nav-section-label">Principal</p>

    <nav class="admin-nav">

        <a href="{{ route('admin.dashboard') }}"
           class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

    </nav>

    <p class="nav-section-label mt-4">Catálogo</p>

    <nav class="admin-nav">

        <a href="{{ route('admin.categories.index') }}"
           class="admin-nav-link {{ request()->routeIs('admin.categories.*') ? 'is-active' : '' }}">
            <i class="bi bi-tags"></i>
            <span>Categorías</span>
        </a>

        <a href="#" class="admin-nav-link">
            <i class="bi bi-bookmark"></i>
            <span>Marcas</span>
            <span class="nav-soon">Pronto</span>
        </a>

        <a href="#" class="admin-nav-link">
            <i class="bi bi-box-seam"></i>
            <span>Productos</span>
            <span class="nav-soon">Pronto</span>
        </a>

    </nav>

    <p class="nav-section-label mt-4">Ventas</p>

    <nav class="admin-nav">

        <a href="#" class="admin-nav-link">
            <i class="bi bi-bag-check"></i>
            <span>Pedidos</span>
            <span class="nav-soon">Pronto</span>
        </a>

        <a href="#" class="admin-nav-link">
            <i class="bi bi-people"></i>
            <span>Clientes</span>
            <span class="nav-soon">Pronto</span>
        </a>

    </nav>

    <div class="sidebar-bottom">

        <a href="#" class="admin-nav-link">
            <i class="bi bi-gear"></i>
            <span>Configuración</span>
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button class="admin-nav-link nav-logout" type="submit">
                <i class="bi bi-box-arrow-left"></i>
                <span>Cerrar sesión</span>
            </button>

        </form>

    </div>

</aside>