<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · RestauManager</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --rm-sidebar-width: 260px;
            --rm-sidebar-bg: #1b1f27;
            --rm-sidebar-hover: #262b36;
            --rm-sidebar-active: #3b82f6;
            --rm-brand: #f97316;
        }

        body {
            background: #f4f6f9;
            font-size: .925rem;
        }

        /* ---------- Sidebar ---------- */
        .rm-sidebar {
            width: var(--rm-sidebar-width);
            background: var(--rm-sidebar-bg);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1030;
            overflow-y: auto;
            padding-bottom: 1.5rem;
        }

        .rm-sidebar .rm-brand {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: 1.15rem 1.25rem;
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            border-bottom: 1px solid rgba(255, 255, 255, .07);
        }

        .rm-sidebar .rm-brand i {
            font-size: 1.5rem;
            color: var(--rm-brand);
        }

        .rm-nav-section {
            padding: 1rem 1.25rem .35rem;
            font-size: .68rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #7b8294;
            font-weight: 700;
        }

        .rm-sidebar .nav-link {
            color: #b6bcc9;
            border-radius: .5rem;
            margin: .1rem .75rem;
            padding: .5rem .75rem;
            display: flex;
            align-items: center;
            gap: .7rem;
            font-weight: 500;
        }

        .rm-sidebar .nav-link i {
            font-size: 1.05rem;
            width: 1.15rem;
            text-align: center;
        }

        .rm-sidebar .nav-link:hover {
            background: var(--rm-sidebar-hover);
            color: #fff;
        }

        .rm-sidebar .nav-link.active {
            background: var(--rm-sidebar-active);
            color: #fff;
            box-shadow: 0 4px 12px rgba(59, 130, 246, .35);
        }

        /* ---------- Main ---------- */
        .rm-main {
            margin-left: var(--rm-sidebar-width);
            min-height: 100vh;
        }

        .rm-topbar {
            background: #fff;
            border-bottom: 1px solid #e6e9ef;
            padding: .85rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .rm-content {
            padding: 1.5rem;
        }

        .rm-card {
            border: 0;
            box-shadow: 0 1px 3px rgba(16, 24, 40, .06), 0 1px 2px rgba(16, 24, 40, .04);
            border-radius: .75rem;
        }

        .rm-card-header {
            background: #fff;
            border-bottom: 1px solid #eef1f5;
            font-weight: 600;
            padding: .9rem 1.25rem;
        }

        .rm-card-body {
            padding: 1.25rem;
        }

        .table thead th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6b7280;
            font-weight: 700;
            white-space: nowrap;
        }

        .table > :not(caption) > * > * {
            padding: .7rem .85rem;
        }

        .rm-stat {
            border: 0;
            border-radius: .75rem;
            color: #fff;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .12);
        }

        .rm-stat .rm-stat-icon {
            font-size: 2.4rem;
            opacity: .35;
        }

        .rm-stat .rm-stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1;
        }

        .rm-stat .rm-stat-label {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            opacity: .9;
        }

        .page-title {
            font-weight: 700;
            font-size: 1.15rem;
            margin: 0;
        }

        .page-subtitle {
            font-size: .8rem;
            color: #8a91a0;
        }

        .rm-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--rm-sidebar-active);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .rm-empty {
            padding: 2.5rem 1rem;
            color: #98a0af;
            text-align: center;
        }

        .rm-empty i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: .5rem;
            opacity: .5;
        }

        /* Badge palette extra */
        .bg-purple { background-color: #7c3aed !important; color: #fff !important; }

        .form-select-lg, .form-control-lg { font-size: .95rem; }

        @media (max-width: 991.98px) {
            .rm-sidebar { transform: translateX(-100%); transition: transform .25s ease; }
            .rm-sidebar.show { transform: translateX(0); }
            .rm-main { margin-left: 0; }
        }
    </style>
</head>
<body>
@php($iniciales = strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)))

<nav class="rm-sidebar" id="rmSidebar">
    <a href="{{ route('dashboard') }}" class="rm-brand">
        <i class="bi bi-shop"></i>
        <span>Restau<span style="color: var(--rm-brand)">Manager</span></span>
    </a>

    <div class="rm-nav-section">Principal</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
    </ul>

    <div class="rm-nav-section">Operación</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}">
                <i class="bi bi-receipt-cutoff"></i> Órdenes / POS
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('kitchen.*') ? 'active' : '' }}" href="{{ route('kitchen.index') }}">
                <i class="bi bi-fire"></i> Cocina
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('bills.*') ? 'active' : '' }}" href="{{ route('bills.index') }}">
                <i class="bi bi-cash-stack"></i> Facturación
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('tables.*') ? 'active' : '' }}" href="{{ route('tables.index') }}">
                <i class="bi bi-grid-3x3-gap"></i> Mesas
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('reservations.*') ? 'active' : '' }}" href="{{ route('reservations.index') }}">
                <i class="bi bi-calendar-check"></i> Reservas
            </a>
        </li>
    </ul>

    <div class="rm-nav-section">Menú</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('menu-categories.*') ? 'active' : '' }}" href="{{ route('menu-categories.index') }}">
                <i class="bi bi-bookmark"></i> Categorías
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('menu-items.*') ? 'active' : '' }}" href="{{ route('menu-items.index') }}">
                <i class="bi bi-egg-fried"></i> Platos y bebidas
            </a>
        </li>
    </ul>

    <div class="rm-nav-section">Gestión</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}">
                <i class="bi bi-box-seam"></i> Inventario
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">
                <i class="bi bi-people"></i> Clientes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('employees.*') || request()->routeIs('shifts.*') ? 'active' : '' }}" href="{{ route('employees.index') }}">
                <i class="bi bi-person-badge"></i> Empleados
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('shifts.*') ? 'active' : '' }}" href="{{ route('shifts.index') }}">
                <i class="bi bi-clock-history"></i> Turnos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
                <i class="bi bi-truck"></i> Proveedores
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}" href="{{ route('purchases.index') }}">
                <i class="bi bi-cart-check"></i> Órdenes de compra
            </a>
        </li>
    </ul>

    <div class="rm-nav-section">Análisis</div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                <i class="bi bi-graph-up"></i> Reportes
            </a>
        </li>
    </ul>
</nav>

<div class="rm-main">
    <header class="rm-topbar d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none" type="button" onclick="document.getElementById('rmSidebar').classList.toggle('show')">
                <i class="bi bi-list"></i>
            </button>
            <div>
                <h1 class="page-title">@yield('title', 'Dashboard')</h1>
                @hasSection('breadcrumb')
                    <div class="page-subtitle">@yield('breadcrumb')</div>
                @endif
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary" title="Ir al inicio">
                <i class="bi bi-house-door"></i>
            </a>
            <div class="dropdown">
                <a class="d-flex align-items-center gap-2 text-decoration-none text-dark dropdown-toggle"
                   data-bs-toggle="dropdown" href="#" role="button">
                    <span class="rm-avatar">{{ $iniciales }}</span>
                    <span class="d-none d-md-inline small">
                        <strong>{{ auth()->user()->name ?? '—' }}</strong><br>
                        <span class="text-muted">{{ optional(auth()->user())->isAdmin() ? 'Administrador' : 'Usuario' }}</span>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="dropdown-item" type="submit">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <main class="rm-content">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (isset($errors) && $errors->any())
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>Revisa los siguientes campos:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
