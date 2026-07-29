@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')

    <section class="page-heading">
        <div>
            <p class="eyebrow">Vista general</p>
            <h1>Buenos días, {{ strtok(auth()->user()->name ?? 'Administrador', ' ') }}</h1>
            <p class="page-description">Aquí tienes un resumen de tu tienda hoy.</p>
        </div>
        <button class="btn btn-primary admin-action" type="button" disabled title="Disponible al habilitar productos">
            <i class="bi bi-plus-lg"></i> Nuevo producto
        </button>
    </section>

    <section class="metrics-grid">
        <article class="metric-card"><div class="metric-icon is-blue"><i class="bi bi-box-seam"></i></div><div><p>Productos</p><strong>0</strong><span>Aún no hay productos</span></div></article>
        <article class="metric-card"><div class="metric-icon is-violet"><i class="bi bi-bag-check"></i></div><div><p>Pedidos</p><strong>0</strong><span>Sin pedidos este mes</span></div></article>
        <article class="metric-card"><div class="metric-icon is-orange"><i class="bi bi-cash-stack"></i></div><div><p>Ventas</p><strong>S/ 0.00</strong><span>Sin ventas registradas</span></div></article>
        <article class="metric-card"><div class="metric-icon is-green"><i class="bi bi-people"></i></div><div><p>Clientes</p><strong>0</strong><span>Base de clientes vacía</span></div></article>
    </section>

    <section class="dashboard-grid">
        <article class="dashboard-card activity-card">
            <div class="card-heading"><div><h2>Actividad reciente</h2><p>Los últimos movimientos de tu tienda aparecerán aquí.</p></div></div>
            <div class="empty-state"><span class="empty-icon"><i class="bi bi-activity"></i></span><h3>Aún no hay actividad</h3><p>Cuando empieces a gestionar tu tienda, verás las novedades aquí.</p></div>
        </article>
        <article class="dashboard-card quick-card">
            <div class="card-heading"><div><h2>Acciones rápidas</h2><p>Accesos frecuentes</p></div></div>
            <div class="quick-actions">
                <button disabled><i class="bi bi-plus-circle"></i><span>Crear producto</span><small>Pronto</small></button>
                <button disabled><i class="bi bi-receipt"></i><span>Ver pedidos</span><small>Pronto</small></button>
                <button disabled><i class="bi bi-bar-chart"></i><span>Consultar reportes</span><small>Pronto</small></button>
            </div>
        </article>
    </section>

@endsection
