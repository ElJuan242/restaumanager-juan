@extends('layouts.app')

@section('title', 'Mesas')
@section('breadcrumb', 'Mesas del restaurante')

@section('content')
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach ($estados as $valor => $etiqueta)
            <span class="badge {{ \App\Enums\TableStatus::from($valor)->badge() }} fs-6">
                {{ $etiqueta }}: {{ $conteos[$valor] ?? 0 }}
            </span>
        @endforeach
    </div>

    <div class="card rm-card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('tables.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Buscar por número</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="{{ $filtros['q'] ?? '' }}" class="form-control" placeholder="Ej. M-01, T-02...">
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Estado</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($estados as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(($filtros['status'] ?? '') === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Ubicación</label>
                    <select name="location" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($ubicaciones as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(($filtros['location'] ?? '') === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Capacidad mínima</label>
                    <input type="number" name="min_capacity" min="1" step="1" class="form-control"
                           value="{{ $filtros['min_capacity'] ?? '' }}" placeholder="Ej. 4">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark flex-fill">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="{{ route('tables.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card rm-card">
        <div class="rm-card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-grid-3x3-gap me-2"></i>Mesas <span class="badge bg-secondary">{{ $registros->total() }}</span></span>
            <a href="{{ route('tables.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nueva mesa
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mesa</th>
                        <th class="text-center">Capacidad</th>
                        <th>Ubicación</th>
                        <th>Estado</th>
                        <th>Activa</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registros as $mesa)
                        <tr>
                            <td>
                                <a href="{{ route('tables.show', $mesa) }}" class="fw-semibold text-decoration-none">
                                    {{ $mesa->table_number }}
                                </a>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $mesa->capacity }} personas</span>
                            </td>
                            <td>{!! badge($mesa->locationEnum()) !!}</td>
                            <td>
                                {!! badge($mesa->status) !!}
                                @if ($mesa->status === \App\Enums\TableStatus::Cleaning && $mesa->isDelayedCleaning())
                                    <span class="badge bg-danger ms-1" title="Más de 15 minutos en limpieza">
                                        <i class="bi bi-clock-history"></i> Retrasada
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($mesa->is_active)
                                    <span class="badge bg-success">Activa</span>
                                @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex align-items-center gap-1">
                                    @if ($mesa->status === \App\Enums\TableStatus::Cleaning)
                                        <form method="POST" action="{{ route('tables.ready', $mesa) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-success" title="Marcar mesa como lista y disponible">
                                                <i class="bi bi-check2-circle me-1"></i>Mesa lista
                                            </button>
                                        </form>
                                    @endif
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('tables.show', $mesa) }}" class="btn btn-outline-secondary" title="Ver detalle">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('tables.edit', $mesa) }}" class="btn btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" title="Eliminar"
                                                data-bs-toggle="modal" data-bs-target="#modalEliminar{{ $mesa->id }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="rm-empty">
                                    <i class="bi bi-inbox"></i>
                                    No se encontraron mesas con los filtros aplicados.
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
    @foreach ($registros as $mesa)
        <div class="modal fade" id="modalEliminar{{ $mesa->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('tables.destroy', $mesa) }}" class="modal-content">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Eliminar mesa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        ¿Seguro que deseas eliminar la mesa <strong>{{ $mesa->table_number }}</strong>?
                        <div class="small text-muted mt-2">
                            No será posible eliminarla si tiene reservas u órdenes activas.
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
