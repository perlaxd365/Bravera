<header class="admin-topbar">
    <button class="sidebar-toggle d-lg-none" type="button" aria-label="Abrir menú" data-sidebar-toggle>
        <i class="bi bi-list"></i>
    </button>
    <div class="topbar-title">Panel administrativo</div>
    <div class="topbar-user">
        <span class="user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
        <div class="d-none d-sm-block">
            <span class="user-name">{{ auth()->user()->name ?? 'Usuario' }}</span>
            <span class="user-role">{{ auth()->check() ? 'Bienvenido: ' . auth()->user()->email : 'No autenticado' }}</span>
            
        </div>
    </div>
</header>
