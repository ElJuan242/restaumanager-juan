@extends('layouts.app')

@section('title', 'Platos y bebidas')
@section('breadcrumb', 'Menú / Platos y bebidas')

@section('content')
    <div class="card rm-card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('menu-items.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Buscar por nombre</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="{{ $filtros['q'] ?? '' }}" class="form-control" placeholder="Ej. Bandeja, Limonada...">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Categoría</label>
                    <select name="category" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected((string) ($filtros['category'] ?? '') === (string) $categoria->id)>
                                {{ $categoria->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Disponibilidad</label>
                    <select name="availability" class="form-select">
                        <option value="">Todas</option>
                        <option value="available" @selected(($filtros['availability'] ?? '') === 'available')>Disponibles</option>
                        <option value="unavailable" @selected(($filtros['availability'] ?? '') === 'unavailable')>No disponibles</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="featured" value="1" id="featured"
                               @checked(($filtros['featured'] ?? '') === '1')>
                        <label class="form-check-label small fw-semibold" for="featured">
                            <i class="bi bi-star-fill text-warning me-1"></i>Solo destacados
                        </label>
                    </div>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark flex-fill">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="{{ route('menu-items.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card rm-card">
        <div class="rm-card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-egg-fried me-2"></i>Platos y bebidas <span class="badge bg-secondary">{{ $registros->total() }}</span></span>
            <a href="{{ route('menu-items.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nuevo plato o bebida
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th class="text-end">Precio</th>
                        <th class="text-end">Ingredientes</th>
                        <th>Disponibilidad</th>
                        <th class="text-center">Destacado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registros as $registro)
                        <tr>
                            <td>
                                <a href="{{ route('menu-items.show', $registro) }}" class="fw-semibold text-decoration-none">
                                    {{ $registro->name }}
                                </a>
                                @if ($registro->description)
                                    <div class="small text-muted text-truncate" style="max-width: 260px;">{{ $registro->description }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary">{{ $registro->category?->name ?? '—' }}</span>
                            </td>
                            <td class="text-end">{{ money_exact($registro->price) }}</td>
                            <td class="text-end">
                                <span class="badge bg-light text-dark border">{{ count($registro->ingredientsList()) }}</span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('menu-items.toggle-available', $registro) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="return_to" value="index">
                                    <select name="availability_toggle"
                                            class="form-select form-select-sm {{ $registro->is_available ? 'text-success' : 'text-secondary' }}"
                                            style="width: auto;" onchange="this.form.submit()">
                                        <option value="1" @selected($registro->is_available)>Disponible</option>
                                        <option value="0" @selected(! $registro->is_available)>No disponible</option>
                                    </select>
                                </form>
                            </td>
                            <td class="text-center">
                                @if ($registro->is_featured)
                                    <i class="bi bi-star-fill text-warning" title="Destacado"></i>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('menu-items.show', $registro) }}" class="btn btn-outline-secondary" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('menu-items.edit', $registro) }}" class="btn btn-outline-primary" title="Editar">
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
                            <td colspan="7">
                                <div class="rm-empty">
                                    <i class="bi bi-inbox"></i>
                                    No se encontraron platos o bebidas con los filtros aplicados.
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
                <form method="POST" action="{{ route('menu-items.destroy', $registro) }}" class="modal-content">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Eliminar plato o bebida</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        ¿Seguro que deseas eliminar <strong>{{ $registro->name }}</strong> del menú?
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
