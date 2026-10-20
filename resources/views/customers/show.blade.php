@extends('layouts.app')

@section('title', $cliente->name)
@section('breadcrumb', 'Clientes / Detalle del cliente')

@section('content')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card rm-card mb-3">
                <div class="rm-card-header d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-person-vcard me-2"></i>{{ $cliente->name }}
                        @if ($cliente->is_vip)
                            <span class="badge bg-warning text-dark ms-1"><i class="bi bi-star-fill"></i> VIP</span>
                        @endif
                    </span>
                    <div class="d-flex gap-2">
                        <a href="{{ route('customers.edit', $cliente) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil me-1"></i>Editar
                        </a>
                        <form method="POST" action="{{ route('customers.destroy', $cliente) }}"
                              onsubmit="return confirm('¿Seguro que deseas eliminar este cliente?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash me-1"></i>Eliminar
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted">Teléfono</dt>
                        <dd class="col-sm-7">{{ $cliente->phone ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted">Correo</dt>
                        <dd class="col-sm-7">{{ $cliente->email ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted">Dirección</dt>
                        <dd class="col-sm-7">{{ $cliente->address ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted">Fecha de nacimiento</dt>
                        <dd class="col-sm-7">{{ date_es($cliente->birth_date) }}</dd>

                        <dt class="col-sm-5 text-muted">Cliente desde</dt>
                        <dd class="col-sm-7">{{ date_es($cliente->created_at) }}</dd>

                        <dt class="col-sm-5 text-muted">Notas</dt>
                        <dd class="col-sm-7">{{ $cliente->notes ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card rm-card">
                <div class="rm-card-header"><i class="bi bi-graph-up me-2"></i>Métricas del cliente</div>

                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted"><i class="bi bi-door-open me-2"></i>Visitas registradas</span>
                        <span class="fw-bold fs-5">{{ $cliente->total_visits }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted"><i class="bi bi-cash-stack me-2"></i>Total gastado</span>
                        <span class="fw-bold fs-5">{{ money($cliente->total_spent) }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted"><i class="bi bi-calendar-check me-2"></i>Última visita</span>
                        <span class="fw-semibold">{{ date_es($cliente->last_visit) }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted"><i class="bi bi-star me-2"></i>Estado VIP</span>
                        @if ($cliente->is_vip)
                            <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> VIP</span>
                        @else
                            <span class="badge bg-secondary">Cliente estándar</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card rm-card mb-3">
                <div class="rm-card-header"><i class="bi bi-receipt-cutoff me-2"></i>Últimas órdenes</div>

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
                                    <td class="fw-semibold">{{ $orden->order_number }}</td>
                                    <td>{{ date_es($orden->created_at) }}</td>
                                    <td class="text-end">{{ money($orden->total) }}</td>
                                    <td>{!! badge($orden->status) !!}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="rm-empty">
                                            <i class="bi bi-inbox"></i>
                                            Este cliente aún no tiene órdenes registradas.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card rm-card">
                <div class="rm-card-header"><i class="bi bi-calendar-week me-2"></i>Próximas reservas</div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha y hora</th>
                                <th class="text-end">Personas</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reservas as $reserva)
                                <tr>
                                    <td class="fw-semibold">
                                        {{ date_es($reserva->reservation_date) }}
                                        <span class="text-muted small">· {{ time_es($reserva->reservation_date) }}</span>
                                    </td>
                                    <td class="text-end">{{ $reserva->people_count }}</td>
                                    <td>{!! badge($reserva->status) !!}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">
                                        <div class="rm-empty">
                                            <i class="bi bi-inbox"></i>
                                            No hay reservas vinculadas a este cliente.
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
