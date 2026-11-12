@extends('layouts.app')

@section('title', 'Reservas')
@section('breadcrumb', 'Gestión de reservas de mesas')

@section('content')
    <div class="card rm-card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('reservations.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Buscar por cliente</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="{{ $filtros['q'] ?? '' }}" class="form-control" placeholder="Ej. María Pérez...">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Estado</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($estados as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(($filtros['status'] ?? '') === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Fecha</label>
                    <select name="date" class="form-select">
                        <option value="">Todas</option>
                        <option value="today" @selected(($filtros['date'] ?? '') === 'today')>Hoy</option>
                        <option value="upcoming" @selected(($filtros['date'] ?? '') === 'upcoming')>Próximas</option>
                        <option value="past" @selected(($filtros['date'] ?? '') === 'past')>Pasadas</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark flex-fill">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card rm-card">
        <div class="rm-card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-calendar-check me-2"></i>Reservas <span class="badge bg-secondary">{{ $registros->total() }}</span></span>
            <a href="{{ route('reservations.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nueva reserva
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Fecha y hora</th>
                        <th class="text-center">Personas</th>
                        <th>Mesa</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registros as $reserva)
                        <tr>
                            <td>
                                <a href="{{ route('reservations.show', $reserva) }}" class="fw-semibold text-decoration-none">
                                    {{ $reserva->client_name }}
                                </a>
                                @if ($reserva->notes)
                                    <div class="small text-muted text-truncate" style="max-width: 220px;">{{ $reserva->notes }}</div>
                                @endif
                            </td>
                            <td>{{ $reserva->client_phone ?? '—' }}</td>
                            <td class="text-nowrap">
                                <i class="bi bi-calendar3 text-muted me-1"></i>{{ date_es($reserva->reservation_date) }}
                                <div class="small text-muted"><i class="bi bi-clock me-1"></i>{{ time_es($reserva->reservation_date) }}</div>
                                @if ($reserva->reservation_end)
                                    <div class="small text-muted"><i class="bi bi-clock-history me-1"></i>hasta {{ time_es($reserva->reservation_end) }}</div>
                                @endif
                            </td>
                            <td class="text-center fw-semibold">{{ $reserva->people_count }}</td>
                            <td>
                                @if ($reserva->table)
                                    <a href="{{ route('tables.show', $reserva->table) }}" class="text-decoration-none">
                                        {{ $reserva->table->table_number }}
                                    </a>
                                    <div class="small text-muted">Cap. {{ $reserva->table->capacity }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{!! badge($reserva->status) !!}</td>
                            <td class="text-end text-nowrap">
                                <div class="d-flex gap-1 align-items-center justify-content-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('reservations.show', $reserva) }}" class="btn btn-outline-secondary" title="Ver detalle">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('reservations.edit', $reserva) }}" class="btn btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" title="Eliminar"
                                                data-bs-toggle="modal" data-bs-target="#modalEliminar{{ $reserva->id }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>

                                    <form method="POST" action="{{ route('reservations.status', $reserva) }}" title="Cambio rápido de estado">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="form-select form-select-sm" style="width: auto;"
                                                onchange="this.form.submit()" aria-label="Cambiar estado">
                                            @foreach ($estados as $valor => $etiqueta)
                                                <option value="{{ $valor }}" @selected($reserva->status->value === $valor)>{{ $etiqueta }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="rm-empty">
                                    <i class="bi bi-inbox"></i>
                                    No se encontraron reservas con los filtros aplicados.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($registros->hasPages())
            <div class="card-footer bg-white d-flex justify-content-center">
                {{ $registros->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    {{-- Modales: eliminación --}}
    @foreach ($registros as $reserva)
        <div class="modal fade" id="modalEliminar{{ $reserva->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('reservations.destroy', $reserva) }}" class="modal-content">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Eliminar reserva</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        ¿Seguro que deseas eliminar la reserva de <strong>{{ $reserva->client_name }}</strong>
                        del {{ date_es($reserva->reservation_date) }}?
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
