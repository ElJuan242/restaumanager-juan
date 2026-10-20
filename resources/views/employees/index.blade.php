@extends('layouts.app')

@section('title', 'Empleados')
@section('breadcrumb', 'Personal del restaurante y sus roles')

@section('content')
    <div class="card rm-card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('employees.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Buscar por nombre</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="{{ $filtros['q'] ?? '' }}" class="form-control" placeholder="Ej. Juan, Ana...">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Rol</label>
                    <select name="role" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($roles as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(($filtros['role'] ?? '') === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Estado</label>
                    <select name="active" class="form-select">
                        <option value="">Todos</option>
                        <option value="1" @selected(($filtros['active'] ?? '') === '1')>Activos</option>
                        <option value="0" @selected(($filtros['active'] ?? '') === '0')>Inactivos</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark flex-fill">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card rm-card">
        <div class="rm-card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-person-badge me-2"></i>Empleados <span class="badge bg-secondary">{{ $registros->total() }}</span></span>
            <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nuevo empleado
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Empleado</th>
                        <th>Rol</th>
                        <th>Documento</th>
                        <th class="text-end">Salario</th>
                        <th>Contratación</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registros as $empleado)
                        <tr>
                            <td>
                                <a href="{{ route('employees.show', $empleado) }}" class="fw-semibold text-decoration-none">
                                    {{ $empleado->name }}
                                </a>
                                @if ($empleado->position)
                                    <div class="small text-muted">{{ $empleado->position }}</div>
                                @endif
                            </td>
                            <td>{!! badge($empleado->role) !!}</td>
                            <td>{{ $empleado->document_id ?? '—' }}</td>
                            <td class="text-end">{{ $empleado->salary !== null ? money($empleado->salary) : '—' }}</td>
                            <td>{{ date_es($empleado->hired_at) }}</td>
                            <td>
                                @if ($empleado->is_active)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('employees.show', $empleado) }}" class="btn btn-outline-secondary" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('shifts.create', ['employee_id' => $empleado->id]) }}" class="btn btn-outline-secondary" title="Nuevo turno">
                                        <i class="bi bi-clock-plus"></i>
                                    </a>
                                    <a href="{{ route('employees.edit', $empleado) }}" class="btn btn-outline-secondary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Eliminar"
                                            data-bs-toggle="modal" data-bs-target="#modalEliminar{{ $empleado->id }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="rm-empty">
                                    <i class="bi bi-inbox"></i>
                                    No se encontraron empleados con los filtros aplicados.
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
    @foreach ($registros as $empleado)
        <div class="modal fade" id="modalEliminar{{ $empleado->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('employees.destroy', $empleado) }}" class="modal-content">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Eliminar empleado</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        ¿Seguro que deseas eliminar a <strong>{{ $empleado->name }}</strong>?
                        <div class="small text-muted mt-2">
                            <i class="bi bi-info-circle me-1"></i>También se eliminarán sus turnos asociados.
                        </div>
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
