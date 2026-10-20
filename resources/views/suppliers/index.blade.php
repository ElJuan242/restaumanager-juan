@extends('layouts.app')

@section('title', 'Proveedores')
@section('breadcrumb', 'Gestión de proveedores y suministros')

@section('content')
    <div class="card rm-card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('suppliers.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Buscar por nombre</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="{{ $filtros['q'] ?? '' }}" class="form-control" placeholder="Ej. Distribuidora, Carnes...">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Categoría de suministro</label>
                    <select name="category" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($categorias as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(($filtros['category'] ?? '') === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="active" value="1" id="active"
                               @checked(isset($filtros['active']))>
                        <label class="form-check-label small fw-semibold" for="active">
                            <i class="bi bi-check-circle text-success me-1"></i>Solo proveedores activos
                        </label>
                    </div>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark flex-fill">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card rm-card">
        <div class="rm-card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-truck me-2"></i>Proveedores <span class="badge bg-secondary">{{ $registros->total() }}</span></span>
            <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nuevo proveedor
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Proveedor</th>
                        <th>Contacto</th>
                        <th>Categoría</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registros as $registro)
                        <tr>
                            <td>
                                <a href="{{ route('suppliers.show', $registro) }}" class="fw-semibold text-decoration-none">
                                    {{ $registro->name }}
                                </a>
                                @if ($registro->address)
                                    <div class="small text-muted text-truncate" style="max-width: 260px;">{{ $registro->address }}</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $registro->contact_name ?? '—' }}</div>
                                @if ($registro->email)
                                    <div class="small text-muted">{{ $registro->email }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($registro->category)
                                    <span class="badge bg-light text-dark border">{{ $registro->category }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>{{ $registro->phone ?? '—' }}</td>
                            <td>
                                @if ($registro->is_active)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('suppliers.show', $registro) }}" class="btn btn-outline-secondary" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('suppliers.edit', $registro) }}" class="btn btn-outline-secondary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Eliminar"
                                            data-bs-toggle="modal" data-bs-target="#modalEliminar{{ $registro->id }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="rm-empty">
                                    <i class="bi bi-inbox"></i>
                                    No se encontraron proveedores con los filtros aplicados.
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
    @foreach ($registros as $registro)
        <div class="modal fade" id="modalEliminar{{ $registro->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('suppliers.destroy', $registro) }}" class="modal-content">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Eliminar proveedor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        ¿Seguro que deseas eliminar el proveedor <strong>{{ $registro->name }}</strong>?
                        <div class="small text-muted mt-2">
                            No se puede eliminar si tiene órdenes de compra asociadas.
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
