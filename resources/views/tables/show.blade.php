@extends('layouts.app')

@section('title', 'Mesa ' . $registro->table_number)
@section('breadcrumb', 'Mesas / Detalle de la mesa')

@section('content')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card rm-card">
                <div class="rm-card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-grid-3x3-gap me-2"></i>Mesa {{ $registro->table_number }}</span>
                    <div class="d-flex gap-2">
                        @if ($registro->status === \App\Enums\TableStatus::Cleaning)
                            <form method="POST" action="{{ route('tables.ready', $registro) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="bi bi-check2-circle me-1"></i>Mesa lista
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('tables.edit', $registro) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil me-1"></i>Editar
                        </a>
                        <form method="POST" action="{{ route('tables.destroy', $registro) }}"
                              onsubmit="return confirm('¿Seguro que deseas eliminar esta mesa?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash me-1"></i>Eliminar
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-body">
                    @if ($registro->is_active && $registro->status->value === 'available')
                        <div class="alert alert-success py-2 mb-3">
                            <i class="bi bi-check-circle me-1"></i><strong>Disponible</strong> para recibir clientes.
                        </div>
                    @elseif ($registro->is_active && $registro->status->value === 'cleaning')
                        <div class="alert alert-info py-2 mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <i class="bi bi-stars me-1"></i>Mesa en proceso de <strong>limpieza</strong>.
                                @if ($registro->cleaning_started_at)
                                    <span class="small d-block text-muted">Inició hace {{ $registro->cleaning_started_at->diffForHumans(null, true) }} ({{ time_es($registro->cleaning_started_at) }}).</span>
                                @endif
                                @if ($registro->isDelayedCleaning())
                                    <span class="badge bg-danger mt-1">Superó el tiempo estándar de rotación (15 min)</span>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('tables.ready', $registro) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="bi bi-check2-circle me-1"></i>Marcar lista
                                </button>
                            </form>
                        </div>
                    @elseif (! $registro->is_active)
                        <div class="alert alert-secondary py-2 mb-3">
                            <i class="bi bi-pause-circle me-1"></i>Mesa <strong>inactiva</strong>, no acepta reservas ni órdenes.
                        </div>
                    @else
                        <div class="alert alert-warning py-2 mb-3">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            En este momento está <strong>{{ $registro->status->label() }}</strong>.
                        </div>
                    @endif

                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Número de mesa</dt>
                        <dd class="col-sm-8 fw-semibold">{{ $registro->table_number }}</dd>

                        <dt class="col-sm-4 text-muted">Capacidad</dt>
                        <dd class="col-sm-8">{{ $registro->capacity }} personas</dd>

                        <dt class="col-sm-4 text-muted">Ubicación</dt>
                        <dd class="col-sm-8">{!! badge($registro->locationEnum()) !!}</dd>

                        <dt class="col-sm-4 text-muted">Estado</dt>
                        <dd class="col-sm-8">{!! badge($registro->status) !!}</dd>

                        <dt class="col-sm-4 text-muted">Activa</dt>
                        <dd class="col-sm-8">
                            @if ($registro->is_active)
                                <span class="badge bg-success">Sí</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted">Reservas activas</dt>
                        <dd class="col-sm-8">{{ $registro->reservations()->whereIn('status', ['pending', 'confirmed'])->count() }}</dd>

                        <dt class="col-sm-4 text-muted">Órdenes activas</dt>
                        <dd class="col-sm-8">{{ $registro->orders()->whereIn('status', ['open', 'preparing', 'ready'])->count() }}</dd>

                        @if ($registro->cleaning_started_at)
                            <dt class="col-sm-4 text-muted">Inicio de limpieza</dt>
                            <dd class="col-sm-8">{{ date_es($registro->cleaning_started_at) }} {{ time_es($registro->cleaning_started_at) }}</dd>
                        @endif

                        @if ($registro->ready_confirmed_at)
                            <dt class="col-sm-4 text-muted">Última confirmación lista</dt>
                            <dd class="col-sm-8">
                                {{ date_es($registro->ready_confirmed_at) }} {{ time_es($registro->ready_confirmed_at) }}
                                @if ($registro->readyConfirmedBy)
                                    <span class="text-muted">por {{ $registro->readyConfirmedBy->name }}</span>
                                @endif
                            </dd>
                        @endif

                        <dt class="col-sm-4 text-muted">Registrada</dt>
                        <dd class="col-sm-8">{{ date_es($registro->created_at) }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card rm-card mb-3">
                <div class="rm-card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar-check me-2"></i>Últimas reservas</span>
                    <a href="{{ route('reservations.create') }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i>Nueva reserva
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Cliente</th>
                                <th>Fecha y hora</th>
                                <th class="text-center">Personas</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reservas as $reserva)
                                <tr>
                                    <td>
                                        <a href="{{ route('reservations.show', $reserva) }}" class="fw-semibold text-decoration-none">
                                            {{ $reserva->client_name }}
                                        </a>
                                    </td>
                                    <td class="text-nowrap">
                                        {{ date_es($reserva->reservation_date) }} · {{ time_es($reserva->reservation_date) }}
                                    </td>
                                    <td class="text-center">{{ $reserva->people_count }}</td>
                                    <td>{!! badge($reserva->status) !!}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="rm-empty">
                                            <i class="bi bi-inbox"></i>
                                            Esta mesa aún no tiene reservas registradas.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card rm-card">
                <div class="rm-card-header"><i class="bi bi-receipt-cutoff me-2"></i>Órdenes recientes</div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Orden</th>
                                <th>Fecha</th>
                                <th class="text-end">Total</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ordenes as $orden)
                                <tr>
                                    <td>
                                        <a href="{{ route('orders.show', $orden) }}" class="fw-semibold text-decoration-none">
                                            {{ $orden->order_number }}
                                        </a>
                                    </td>
                                    <td class="text-nowrap">{{ date_es($orden->created_at) }}</td>
                                    <td class="text-end">{{ money($orden->total) }}</td>
                                    <td>{!! badge($orden->status) !!}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="rm-empty">
                                            <i class="bi bi-inbox"></i>
                                            Esta mesa aún no tiene órdenes registradas.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
