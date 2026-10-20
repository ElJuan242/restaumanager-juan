@extends('layouts.app')

@section('title', 'Órdenes / POS')
@section('breadcrumb', 'Órdenes y punto de venta')

@section('content')
    <div class="card rm-card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('orders.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Buscar por Nº de orden</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="{{ $filtros['q'] ?? '' }}" class="form-control" placeholder="Ej. ORD-261020-001">
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

                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Mesa</label>
                    <select name="table" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($mesas as $mesa)
                            <option value="{{ $mesa->id }}" @selected((string) ($filtros['table'] ?? '') === (string) $mesa->id)>{{ $mesa->table_number }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Origen</label>
                    <select name="source" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($origenes as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(($filtros['source'] ?? '') === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark flex-fill">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card rm-card">
        <div class="rm-card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-receipt-cutoff me-2"></i>Órdenes <span class="badge bg-secondary">{{ $registros->total() }}</span></span>
            <div class="d-flex gap-2">
                <a href="{{ route('kitchen.index') }}" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-fire me-1"></i>Vista de cocina
                </a>
                <a href="{{ route('orders.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Nueva orden
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Orden</th>
                        <th>Origen</th>
                        <th>Mesa</th>
                        <th>Cliente</th>
                        <th>Empleado</th>
                        <th class="text-center">Items</th>
                        <th class="text-end">Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registros as $registro)
                        <tr>
                            <td>
                                <a href="{{ route('orders.show', $registro) }}" class="fw-semibold text-decoration-none">
                                    {{ $registro->order_number }}
                                </a>
                            </td>
                            <td>{!! badge($registro->source) !!}</td>
                            <td>
                                @if ($registro->table)
                                    {{ $registro->table->table_number }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $registro->customer?->name ?? '—' }}</td>
                            <td>{{ $registro->employee?->name ?? '—' }}</td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $registro->items_count }}</span>
                            </td>
                            <td class="text-end fw-semibold">{{ money($registro->total) }}</td>
                            <td>{!! badge($registro->status) !!}</td>
                            <td class="text-nowrap small text-muted">
                                {{ date_es($registro->created_at) }}<br>{{ time_es($registro->created_at) }}
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('orders.show', $registro) }}" class="btn btn-outline-secondary" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('orders.edit', $registro) }}" class="btn btn-outline-primary" title="Editar">
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
                            <td colspan="10">
                                <div class="rm-empty">
                                    <i class="bi bi-inbox"></i>
                                    No se encontraron órdenes con los filtros aplicados.
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
                <form method="POST" action="{{ route('orders.destroy', $registro) }}" class="modal-content">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Eliminar orden</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        ¿Seguro que deseas eliminar la orden <strong>{{ $registro->order_number }}</strong>?
                        <div class="small text-muted mt-2">
                            No será posible eliminarla si ya tiene una factura generada.
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
