@extends('layouts.app')

@section('title', 'Clientes')
@section('breadcrumb', 'Ficha de clientes y su historial de visitas')

@section('content')
    <div class="card rm-card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('customers.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Buscar por nombre</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="{{ $filtros['q'] ?? '' }}" class="form-control" placeholder="Ej. María, Carlos...">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="vip" value="1" id="vip"
                               @checked(($filtros['vip'] ?? '') === '1')>
                        <label class="form-check-label small fw-semibold" for="vip">
                            <i class="bi bi-star-fill text-warning me-1"></i>Solo clientes VIP
                        </label>
                    </div>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark flex-fill">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card rm-card">
        <div class="rm-card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-people me-2"></i>Clientes <span class="badge bg-secondary">{{ $registros->total() }}</span></span>
            <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nuevo cliente
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>VIP</th>
                        <th class="text-end">Visitas</th>
                        <th class="text-end">Total gastado</th>
                        <th>Última visita</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registros as $cliente)
                        <tr>
                            <td>
                                <a href="{{ route('customers.show', $cliente) }}" class="fw-semibold text-decoration-none">
                                    {{ $cliente->name }}
                                </a>
                                @if ($cliente->address)
                                    <div class="small text-muted text-truncate" style="max-width: 220px;">{{ $cliente->address }}</div>
                                @endif
                            </td>
                            <td>{{ $cliente->phone ?? '—' }}</td>
                            <td>{{ $cliente->email ?? '—' }}</td>
                            <td>
                                @if ($cliente->is_vip)
                                    <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> VIP</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-end fw-semibold">{{ $cliente->total_visits }}</td>
                            <td class="text-end">{{ money($cliente->total_spent) }}</td>
                            <td>{{ date_es($cliente->last_visit) }}</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('customers.show', $cliente) }}" class="btn btn-outline-secondary" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('customers.edit', $cliente) }}" class="btn btn-outline-secondary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Eliminar"
                                            data-bs-toggle="modal" data-bs-target="#modalEliminar{{ $cliente->id }}">
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
                                    No se encontraron clientes con los filtros aplicados.
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
    @foreach ($registros as $cliente)
        <div class="modal fade" id="modalEliminar{{ $cliente->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('customers.destroy', $cliente) }}" class="modal-content">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Eliminar cliente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        ¿Seguro que deseas eliminar a <strong>{{ $cliente->name }}</strong>?
                        <div class="small text-muted mt-2">
                            Sus órdenes y reservas asociadas se conservarán en el historial.
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
