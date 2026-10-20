@extends('layouts.app')

@section('title', 'Reserva de ' . $registro->client_name)
@section('breadcrumb', 'Reservas / Detalle de la reserva')

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-calendar-check me-2"></i>Reserva de {{ $registro->client_name }}</span>
            <div class="d-flex gap-2">
                <a href="{{ route('reservations.edit', $registro) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-pencil me-1"></i>Editar
                </a>
                <form method="POST" action="{{ route('reservations.destroy', $registro) }}"
                      onsubmit="return confirm('¿Seguro que deseas eliminar esta reserva?');">
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
                <dt class="col-sm-3 text-muted">Cliente</dt>
                <dd class="col-sm-9 fw-semibold">{{ $registro->client_name }}</dd>

                <dt class="col-sm-3 text-muted">Teléfono</dt>
                <dd class="col-sm-9">{{ $registro->client_phone ?? '—' }}</dd>

                <dt class="col-sm-3 text-muted">Correo electrónico</dt>
                <dd class="col-sm-9">{{ $registro->client_email ?? '—' }}</dd>

                <dt class="col-sm-3 text-muted">Fecha y hora</dt>
                <dd class="col-sm-9">
                    {{ date_es($registro->reservation_date) }} · {{ time_es($registro->reservation_date) }}
                </dd>

                <dt class="col-sm-3 text-muted">Número de personas</dt>
                <dd class="col-sm-9 fw-semibold">{{ $registro->people_count }}</dd>

                <dt class="col-sm-3 text-muted">Mesa asignada</dt>
                <dd class="col-sm-9">
                    @if ($registro->table)
                        <a href="{{ route('tables.show', $registro->table) }}" class="fw-semibold text-decoration-none">
                            {{ $registro->table->table_number }}
                        </a>
                        <span class="text-muted ms-2">
                            · Capacidad {{ $registro->table->capacity }} personas
                            · {{ $registro->table->locationEnum()->label() }}
                        </span>
                    @else
                        <span class="text-muted">Sin mesa asignada</span>
                    @endif
                </dd>

                <dt class="col-sm-3 text-muted">Estado</dt>
                <dd class="col-sm-9">{!! badge($registro->status) !!}</dd>

                <dt class="col-sm-3 text-muted">Notas</dt>
                <dd class="col-sm-9">{{ $registro->notes ?? '—' }}</dd>

                <dt class="col-sm-3 text-muted">Registrada</dt>
                <dd class="col-sm-9">{{ date_es($registro->created_at) }}</dd>
            </dl>
        </div>

        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Volver al listado
            </a>

            <form method="POST" action="{{ route('reservations.status', $registro) }}">
                @csrf
                @method('PATCH')
                <div class="d-flex gap-2 align-items-center">
                    <label for="statusShow" class="small fw-semibold mb-0">Cambiar estado:</label>
                    <select name="status" id="statusShow" class="form-select form-select-sm">
                        @foreach ($estados as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($registro->status->value === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-dark btn-sm">
                        <i class="bi bi-check2 me-1"></i>Aplicar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
