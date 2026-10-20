@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Resumen general del restaurante')

@section('content')
    {{-- Tarjetas principales --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card rm-stat h-100" style="background: linear-gradient(135deg,#3b82f6,#1d4ed8);">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="rm-stat-label">Inventario</div>
                        <div class="rm-stat-value">{{ $totalInventario }}</div>
                        <div class="small opacity-75">productos activos</div>
                    </div>
                    <i class="bi bi-box-seam rm-stat-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card rm-stat h-100" style="background: linear-gradient(135deg,#f97316,#c2410c);">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="rm-stat-label">Stock bajo</div>
                        <div class="rm-stat-value">{{ $stockBajo }}</div>
                        <div class="small opacity-75">requieren reposición</div>
                    </div>
                    <i class="bi bi-exclamation-triangle rm-stat-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card rm-stat h-100" style="background: linear-gradient(135deg,#10b981,#047857);">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="rm-stat-label">Reservas hoy</div>
                        <div class="rm-stat-value">{{ $reservasHoy }}</div>
                        <div class="small opacity-75">registradas</div>
                    </div>
                    <i class="bi bi-calendar-check rm-stat-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card rm-stat h-100" style="background: linear-gradient(135deg,#8b5cf6,#6d28d9);">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="rm-stat-label">Mesas</div>
                        <div class="rm-stat-value">{{ $mesasDisponibles }}<span class="fs-6 opacity-75">/{{ $totalMesas }}</span></div>
                        <div class="small opacity-75">disponibles</div>
                    </div>
                    <i class="bi bi-grid-3x3-gap rm-stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Tarjetas complementarias --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6">
            <div class="card rm-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:52px;height:52px;">
                        <i class="bi bi-receipt-cutoff fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Órdenes activas</div>
                        <div class="fs-4 fw-bold">{{ $ordenesActivas }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="card rm-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center bg-success-subtle text-success" style="width:52px;height:52px;">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Ventas de hoy</div>
                        <div class="fs-4 fw-bold">{{ money($ventasHoy) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Reservas próximas --}}
        <div class="col-lg-7">
            <div class="card rm-card mb-3">
                <div class="rm-card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar-event me-2 text-primary"></i>Reservas próximas</span>
                    <a href="{{ route('reservations.index') }}" class="btn btn-sm btn-outline-secondary">Ver todas</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Cliente</th>
                                <th>Fecha y hora</th>
                                <th>Personas</th>
                                <th>Mesa</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reservasProximas as $reserva)
                                <tr>
                                    <td>
                                        <a href="{{ route('reservations.show', $reserva) }}" class="fw-semibold text-decoration-none">
                                            {{ $reserva->client_name }}
                                        </a>
                                    </td>
                                    <td>{{ $reserva->reservation_date->format('d/m/Y H:i') }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $reserva->people_count }}</span></td>
                                    <td>{{ $reserva->table?->table_number ?? 'Sin mesa' }}</td>
                                    <td>{!! badge($reserva->status) !!}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="rm-empty">
                                            <i class="bi bi-calendar-x"></i>
                                            No hay reservas próximas.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card rm-card">
                <div class="rm-card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-receipt-cutoff me-2 text-warning"></i>Órdenes recientes</span>
                    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary">Ver todas</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Orden</th>
                                <th>Mesa</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ordenesRecientes as $orden)
                                <tr>
                                    <td>
                                        <a href="{{ route('orders.show', $orden) }}" class="fw-semibold text-decoration-none">
                                            {{ $orden->order_number }}
                                        </a>
                                    </td>
                                    <td>{{ $orden->table?->table_number ?? '—' }}</td>
                                    <td>{{ $orden->customer?->name ?? 'Sin cliente' }}</td>
                                    <td>{{ money($orden->total) }}</td>
                                    <td>{!! badge($orden->status) !!}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="rm-empty">
                                            <i class="bi bi-inbox"></i>
                                            Todavía no hay órdenes.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Alertas de stock bajo --}}
        <div class="col-lg-5">
            <div class="card rm-card mb-3">
                <div class="rm-card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Alertas de stock bajo</span>
                    <a href="{{ route('inventory.index', ['low_stock' => 1]) }}" class="btn btn-sm btn-outline-secondary">Ver inventario</a>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($alertasStock as $item)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">{{ $item->name }}</div>
                                <div class="small text-muted">{{ $item->category }}</div>
                            </div>
                            <span class="badge bg-danger-subtle text-danger border">
                                {{ rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',') }} / {{ rtrim(rtrim(number_format($item->min_stock, 2, ',', '.'), '0'), ',') }} {{ $item->unit }}
                            </span>
                        </li>
                    @empty
                        <li class="list-group-item">
                            <div class="rm-empty">
                                <i class="bi bi-check-circle"></i>
                                Ningún producto está por debajo del stock mínimo.
                            </div>
                        </li>
                    @endforelse
                </ul>
            </div>

            <div class="card rm-card">
                <div class="rm-card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-check2-square me-2 text-success"></i>Mesas disponibles</span>
                    <a href="{{ route('tables.index') }}" class="btn btn-sm btn-outline-secondary">Ver mesas</a>
                </div>
                <div class="card-body">
                    @if ($mesasLibres->isEmpty())
                        <div class="rm-empty">
                            <i class="bi bi-grid-3x3-gap"></i>
                            No hay mesas disponibles en este momento.
                        </div>
                    @else
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($mesasLibres as $mesa)
                                <a href="{{ route('tables.show', $mesa) }}"
                                   class="badge bg-success text-decoration-none fs-6 py-2 px-3">
                                    Mesa {{ $mesa->table_number }} · {{ $mesa->capacity }}p
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
