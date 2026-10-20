@extends('layouts.app')

@section('title', 'Turnos')
@section('breadcrumb', 'Horarios planificados y ejecutados del personal')

@section('content')
    <div class="card rm-card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('shifts.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Fecha</label>
                    <input type="date" name="date" value="{{ $filtros['date'] }}" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Empleado</label>
                    <select name="employee_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($empleados as $empleado)
                            <option value="{{ $empleado->id }}"
                                    @selected((string) ($filtros['employee_id'] ?? '') === (string) $empleado->id)>
                                {{ $empleado->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-5 d-flex gap-2">
                    <button type="submit" class="btn btn-dark flex-fill">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary" title="Volver a hoy">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card rm-card">
        <div class="rm-card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-clock-history me-2"></i>Turnos del {{ date_es($filtros['date']) }} <span class="badge bg-secondary">{{ $registros->total() }}</span></span>
            <a href="{{ route('shifts.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nuevo turno
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Empleado</th>
                        <th>Fecha</th>
                        <th>Horario planificado</th>
                        <th>Duración</th>
                        <th>Horario real</th>
                        <th>Duración real</th>
                        <th>Notas</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registros as $turno)
                        <tr>
                            <td>
                                @if ($turno->employee)
                                    <a href="{{ route('employees.show', $turno->employee) }}" class="fw-semibold text-decoration-none">
                                        {{ $turno->employee->name }}
                                    </a>
                                    <div class="small text-muted">{!! badge($turno->employee->role) !!}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ date_es($turno->date) }}</td>
                            <td>{{ time_es($turno->start_time) }} – {{ time_es($turno->end_time) }}</td>
                            <td class="fw-semibold">{{ $turno->duration() }}</td>
                            <td>{{ time_es($turno->actual_start) }} – {{ time_es($turno->actual_end) }}</td>
                            <td class="fw-semibold">{{ $turno->actualDuration() }}</td>
                            <td class="small text-muted">{{ $turno->notes ?? '—' }}</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('shifts.edit', $turno) }}" class="btn btn-outline-secondary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Eliminar"
                                            data-bs-toggle="modal" data-bs-target="#modalEliminar{{ $turno->id }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="rm-empty">
                                    <i class="bi bi-inbox"></i>
                                    No hay turnos registrados con los filtros aplicados.
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
    @foreach ($registros as $turno)
        <div class="modal fade" id="modalEliminar{{ $turno->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('shifts.destroy', $turno) }}" class="modal-content">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Eliminar turno</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        ¿Seguro que deseas eliminar el turno de
                        <strong>{{ $turno->employee?->name ?? '—' }}</strong>
                        del {{ date_es($turno->date) }}?
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
