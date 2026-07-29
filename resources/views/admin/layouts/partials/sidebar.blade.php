<aside class="admin-sidebar" id="adminSidebar">

    {{-- Logo --}}
    <div class="brand-wrapper">
        <a class="brand" href="{{ route('admin.dashboard') }}">
            <span class="brand-mark">
                <i class="bi bi-shop"></i>
            </span>

            <div class="brand-info">
                <span class="brand-title">Bravera</span>
                <small class="brand-subtitle">Sistema Ecommerce</small>
            </div>
        </a>
    </div>

    {{-- Principal --}}
    <div class="nav-section">
        <p class="nav-section-label">Principal</p>

        <nav class="admin-nav">
            <a href="{{ route('admin.dashboard') }}"
                class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">

                <i class="bi bi-grid-1x2-fill"></i>

                <span>Dashboard</span>
            </a>
        </nav>
    </div>

    {{-- Catálogo --}}
    <div class="nav-section">

        <p class="nav-section-label">Catálogo</p>

        <nav class="admin-nav">

            <a href="{{ route('admin.categories.index') }}"
                class="admin-nav-link {{ request()->routeIs('admin.categories.*') ? 'is-active' : '' }}">

                <i class="bi bi-tags"></i>

                <span>Categorías</span>

            </a>

            <a href="#" class="admin-nav-link">

                <i class="bi bi-bookmark"></i>

                <span>Marcas</span>

                <span class="nav-soon">Próximamente</span>

            </a>

            <a href="#" class="admin-nav-link">

                <i class="bi bi-box-seam"></i>

                <span>Productos</span>

                <span class="nav-soon">Próximamente</span>

            </a>

            <a href="#" class="admin-nav-link">

                <i class="bi bi-truck"></i>

                <span>Proveedores</span>

                <span class="nav-soon">Próximamente</span>

            </a>

        </nav>

    </div>

    {{-- Ventas --}}
    <div class="nav-section">

        <p class="nav-section-label">Ventas</p>

        <nav class="admin-nav">

            <a href="#" class="admin-nav-link">

                <i class="bi bi-cart-check"></i>

                <span>Pedidos</span>

                <span class="nav-soon">Próximamente</span>

            </a>

            <a href="#" class="admin-nav-link">

                <i class="bi bi-receipt"></i>

                <span>Comprobantes</span>

                <span class="nav-soon">Próximamente</span>

            </a>

        </nav>

    </div>

    {{-- Clientes --}}
    <div class="nav-section">

        <p class="nav-section-label">Clientes</p>

        <nav class="admin-nav">

            <a href="#" class="admin-nav-link">

                <i class="bi bi-people"></i>

                <span>Clientes</span>

                <span class="nav-soon">Próximamente</span>

            </a>

        </nav>

    </div>

    {{-- Inferior --}}
    <div class="sidebar-bottom">

        <a href="#" class="admin-nav-link">

            <i class="bi bi-gear"></i>

            <span>Configuración</span>

        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="admin-nav-link nav-logout">

                <i class="bi bi-box-arrow-left"></i>

                <span>Cerrar sesión</span>

            </button>
        </form>

    </div>

</aside>
