@extends('layouts.app')

@section('title', 'Reportes')
@section('breadcrumb', 'Análisis de ventas, compras y reservas')

@section('content')
    {{-- Filtro de rango de fechas --}}
    <div class="card rm-card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('reports.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Desde</label>
                    <input type="date" name="from" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Hasta</label>
                    <input type="date" name="to" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100">
                        <i class="bi bi-funnel me-1"></i>Aplicar
                    </button>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Últimos 30 días
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if (! $hayDatos)
        <div class="card rm-card">
            <div class="card-body">
                <div class="rm-empty">
                    <i class="bi bi-graph-down"></i>
                    No hay datos para el rango seleccionado. Ajusta las fechas e inténtalo de nuevo.
                </div>
            </div>
        </div>
    @else
        {{-- KPIs principales --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card rm-stat h-100" style="background: linear-gradient(135deg,#10b981,#047857);">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="rm-stat-label">Total ventas</div>
                            <div class="rm-stat-value">{{ money($totalVentas) }}</div>
                            <div class="small opacity-75">facturas pagadas</div>
                        </div>
                        <i class="bi bi-cash-stack rm-stat-icon"></i>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card rm-stat h-100" style="background: linear-gradient(135deg,#3b82f6,#1d4ed8);">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="rm-stat-label">Facturas</div>
                            <div class="rm-stat-value">{{ $cantidadFacturas }}</div>
                            <div class="small opacity-75">pagadas en el rango</div>
                        </div>
                        <i class="bi bi-receipt-cutoff rm-stat-icon"></i>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card rm-stat h-100" style="background: linear-gradient(135deg,#f97316,#c2410c);">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="rm-stat-label">Venta promedio</div>
                            <div class="rm-stat-value">{{ money($ventaPromedio) }}</div>
                            <div class="small opacity-75">por factura</div>
                        </div>
                        <i class="bi bi-graph-up-arrow rm-stat-icon"></i>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card rm-stat h-100" style="background: linear-gradient(135deg,#8b5cf6,#6d28d9);">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="rm-stat-label">Órdenes</div>
                            <div class="rm-stat-value">{{ $cantidadOrdenes }}</div>
                            <div class="small opacity-75">prom. {{ money($valorPromedioOrden) }}</div>
                        </div>
                        <i class="bi bi-basket rm-stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            {{-- Top 10 platos --}}
            <div class="col-lg-7">
                <div class="card rm-card h-100">
                    <div class="rm-card-header"><i class="bi bi-trophy me-2 text-warning"></i>Top 10 platos más vendidos</div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Plato</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">Ingresos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topPlatos as $i => $plato)
                                    <tr>
                                        <td class="fw-bold text-muted">{{ $i + 1 }}</td>
                                        <td class="fw-semibold">{{ $plato->item_name }}</td>
                                        <td class="text-end">{{ (int) $plato->total_qty }}</td>
                                        <td class="text-end fw-semibold">{{ money($plato->total_ingresos) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">
                                            <div class="rm-empty">
                                                <i class="bi bi-inbox"></i>
                                                No hay platos vendidos en el rango.
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Distribución por método de pago --}}
            <div class="col-lg-5">
                <div class="card rm-card h-100">
                    <div class="rm-card-header"><i class="bi bi-credit-card me-2 text-primary"></i>Métodos de pago</div>
                    <div class="card-body">
                        @forelse ($pagos as $pago)
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span>
                                    <span class="badge {{ $pago->badge }}">{{ $pago->label }}</span>
                                    <span class="small text-muted ms-1">{{ $pago->cantidad }} pago(s)</span>
                                </span>
                                <span class="fw-semibold">{{ money($pago->total) }}</span>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar" role="progressbar"
                                     style="width: {{ $pago->porcentaje }}%;"
                                     aria-valuenow="{{ $pago->porcentaje }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        @empty
                            <div class="rm-empty">
                                <i class="bi bi-credit-card"></i>
                                No hay pagos registrados en el rango.
                            </div>
                        @endforelse

                        @if ($pagos->isNotEmpty())
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Total recaudado</span>
                                <span class="fw-bold fs-5">{{ money($totalRecaudado) }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Desglose diario de ventas --}}
        <div class="card rm-card mb-3">
            <div class="rm-card-header"><i class="bi bi-calendar3 me-2 text-success"></i>Desglose diario de ventas</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Día</th>
                            <th class="text-end">Facturas</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ventasDiarias as $dia)
                            <tr>
                                <td>{{ date_es($dia->dia) }}</td>
                                <td class="text-end">{{ $dia->cantidad }}</td>
                                <td class="text-end fw-semibold">{{ money($dia->total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">
                                    <div class="rm-empty">
                                        <i class="bi bi-calendar-x"></i>
                                        No hay ventas registradas en el rango.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($ventasDiarias->isNotEmpty())
                        <tfoot class="table-light">
                            <tr>
                                <td class="fw-bold">Total</td>
                                <td class="text-end fw-bold">{{ $cantidadFacturas }}</td>
                                <td class="text-end fw-bold">{{ money($totalVentas) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <div class="row g-3">
            {{-- Reservas --}}
            <div class="col-lg-4">
                <div class="card rm-card h-100">
                    <div class="rm-card-header"><i class="bi bi-calendar-check me-2 text-info"></i>Reservas</div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted"><i class="bi bi-calendar-event me-2"></i>Total en el rango</span>
                            <span class="fw-bold fs-5">{{ $totalReservas }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="bi bi-check2-circle me-2"></i>Confirmadas</span>
                            <span class="fw-bold fs-5 text-success">{{ $reservasConfirmadas }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Órdenes por origen --}}
            <div class="col-lg-4">
                <div class="card rm-card h-100">
                    <div class="rm-card-header"><i class="bi bi-shop me-2 text-primary"></i>Órdenes por origen</div>
                    <ul class="list-group list-group-flush">
                        @forelse ($ordenesPorOrigen as $origen)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><span class="badge {{ $origen->badge }}">{{ $origen->label }}</span></span>
                                <span class="text-end">
                                    <span class="fw-semibold d-block">{{ $origen->cantidad }}</span>
                                    <span class="small text-muted">{{ money($origen->total) }}</span>
                                </span>
                            </li>
                        @empty
                            <li class="list-group-item">
                                <div class="rm-empty">
                                    <i class="bi bi-inbox"></i>
                                    Sin órdenes en el rango.
                                </div>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- Órdenes por estado --}}
            <div class="col-lg-4">
                <div class="card rm-card h-100">
                    <div class="rm-card-header"><i class="bi bi-diagram-3 me-2 text-warning"></i>Órdenes por estado</div>
                    <ul class="list-group list-group-flush">
                        @forelse ($ordenesPorEstado as $estado)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><span class="badge {{ $estado->badge }}">{{ $estado->label }}</span></span>
                                <span class="text-end">
                                    <span class="fw-semibold d-block">{{ $estado->cantidad }}</span>
                                    <span class="small text-muted">{{ money($estado->total) }}</span>
                                </span>
                            </li>
                        @empty
                            <li class="list-group-item">
                                <div class="rm-empty">
                                    <i class="bi bi-inbox"></i>
                                    Sin órdenes en el rango.
                                </div>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    @endif
@endsection
