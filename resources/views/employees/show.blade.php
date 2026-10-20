@extends('layouts.app')

@section('title', $empleado->name)
@section('breadcrumb', 'Empleados / Detalle del empleado')

@section('content')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card rm-card">
                <div class="rm-card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-person-vcard me-2"></i>{{ $empleado->name }}</span>
                    <div class="d-flex gap-2">
                        <a href="{{ route('shifts.create', ['employee_id' => $empleado->id]) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-clock-plus me-1"></i>Nuevo turno
                        </a>
                        <a href="{{ route('employees.edit', $empleado) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil me-1"></i>Editar
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted">Rol</dt>
                        <dd class="col-sm-7">{!! badge($empleado->role) !!}</dd>

                        <dt class="col-sm-5 text-muted">Cargo</dt>
                        <dd class="col-sm-7">{{ $empleado->position ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted">Teléfono</dt>
                        <dd class="col-sm-7">{{ $empleado->phone ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted">Correo</dt>
                        <dd class="col-sm-7">{{ $empleado->email ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted">Documento</dt>
                        <dd class="col-sm-7">{{ $empleado->document_id ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted">Salario</dt>
                        <dd class="col-sm-7 fw-semibold">{{ $empleado->salary !== null ? money($empleado->salary) : '—' }}</dd>

                        <dt class="col-sm-5 text-muted">Fecha de contratación</dt>
                        <dd class="col-sm-7">{{ date_es($empleado->hired_at) }}</dd>

                        <dt class="col-sm-5 text-muted">Estado</dt>
                        <dd class="col-sm-7">
                            @if ($empleado->is_active)
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5 text-muted">Registrado</dt>
                        <dd class="col-sm-7">{{ date_es($empleado->created_at) }}</dd>
                    </dl>
                </div>

                <div class="card-footer bg-white d-flex justify-content-end gap-2">
                    <form method="POST" action="{{ route('employees.destroy', $empleado) }}"
                          onsubmit="return confirm('¿Seguro que deseas eliminar este empleado? Sus turnos también se eliminarán.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash me-1"></i>Eliminar empleado
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card rm-card">
                <div class="rm-card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clock-history me-2"></i>Historial de turnos <span class="badge bg-secondary">{{ $turnos->count() }}</span></span>
                    <a href="{{ route('shifts.create', ['employee_id' => $empleado->id]) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i>Nuevo turno
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Horario planificado</th>
                                <th>Duración planificada</th>
                                <th>Horario real</th>
                                <th>Duración real</th>
                                <th>Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($turnos as $turno)
                                <tr>
                                    <td class="fw-semibold">{{ date_es($turno->date) }}</td>
                                    <td>{{ time_es($turno->start_time) }} – {{ time_es($turno->end_time) }}</td>
                                    <td>{{ shift_duration($turno->start_time, $turno->end_time) }}</td>
                                    <td>{{ time_es($turno->actual_start) }} – {{ time_es($turno->actual_end) }}</td>
                                    <td class="fw-semibold">{{ $turno->actualDuration() }}</td>
                                    <td class="small text-muted">{{ $turno->notes ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="rm-empty">
                                            <i class="bi bi-inbox"></i>
                                            Este empleado aún no tiene turnos registrados.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-white small text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Se muestran los últimos {{ $turnos->count() }} turnos. Para ver el listado completo consulta el módulo de
                    <a href="{{ route('shifts.index') }}">Turnos</a>.
                </div>
            </div>
        </div>
    </div>
@endsection
