@extends('layouts.app')

@section('title', 'Categorías del menú')
@section('breadcrumb', 'Menú / Categorías de platos y bebidas')

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-bookmark me-2"></i>Categorías <span class="badge bg-secondary">{{ $registros->count() }}</span></span>
            <a href="{{ route('menu-categories.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nueva categoría
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Orden</th>
                        <th>Categoría</th>
                        <th>Descripción</th>
                        <th class="text-end">Platos/Bebidas</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registros as $registro)
                        <tr>
                            <td class="text-muted">{{ $registro->sort_order }}</td>
                            <td>
                                <span class="fw-semibold">{{ $registro->name }}</span>
                                <div class="small text-muted">/ {{ $registro->slug }}</div>
                            </td>
                            <td>
                                <span class="small text-muted text-truncate d-inline-block" style="max-width: 260px;">
                                    {{ $registro->description ?? '—' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="badge bg-light text-dark border">{{ $registro->itemsCount() }}</span>
                            </td>
                            <td>
                                @if ($registro->is_active)
                                    <span class="badge bg-success">Activa</span>
                                @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('menu-categories.edit', $registro) }}" class="btn btn-outline-primary" title="Editar">
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
                                    Aún no hay categorías registradas en el menú.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modales: eliminación --}}
    @foreach ($registros as $registro)
        <div class="modal fade" id="modalEliminar{{ $registro->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('menu-categories.destroy', $registro) }}" class="modal-content">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Eliminar categoría</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        ¿Seguro que deseas eliminar la categoría <strong>{{ $registro->name }}</strong>?
                        <div class="small text-muted mt-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Solo se puede eliminar si no tiene platos o bebidas asociados.
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
