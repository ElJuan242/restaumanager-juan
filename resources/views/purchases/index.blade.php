@extends('layouts.app')

@section('title', 'Órdenes de compra')
@section('breadcrumb', 'Compras a proveedores y recepción de mercancía')

@section('content')
    <div class="card rm-card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('purchases.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Buscar por Nº de orden</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="{{ $filtros['q'] ?? '' }}" class="form-control" placeholder="Ej. PO-261020-001">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Estado</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($estados as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(($filtros['status'] ?? '') === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Proveedor</label>
                    <select name="supplier_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}" @selected((string) ($filtros['supplier_id'] ?? '') === (string) $proveedor->id)>
                                {{ $proveedor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark flex-fill">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card rm-card">
        <div class="rm-card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-cart-check me-2"></i>Órdenes de compra <span class="badge bg-secondary">{{ $registros->total() }}</span></span>
            <a href="{{ route('purchases.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nueva orden
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nº de orden</th>
                        <th>Proveedor</th>
                        <th>Fecha</th>
                        <th>Fecha esperada</th>
                        <th class="text-center">Items</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">IVA</th>
                        <th class="text-end">Total</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registros as $registro)
                        <tr>
                            <td>
                                <a href="{{ route('purchases.show', $registro) }}" class="fw-semibold text-decoration-none">
                                    {{ $registro->po_number }}
                                </a>
                            </td>
                            <td>{{ $registro->supplier?->name ?? '—' }}</td>
                            <td>{{ date_es($registro->order_date) }}</td>
                            <td class="{{ $registro->isOverduePartial() ? 'text-danger fw-semibold' : '' }}">
                                {{ date_es($registro->expected_date) }}
                                @if ($registro->isOverduePartial())
                                    <i class="bi bi-exclamation-triangle ms-1" title="Orden parcial vencida"></i>
                                @endif
                            </td>
                            <td class="text-center"><span class="badge bg-light text-dark border">{{ $registro->items_count }}</span></td>
                            <td class="text-end">{{ money($registro->subtotal) }}</td>
                            <td class="text-end text-muted">{{ money($registro->tax) }}</td>
                            <td class="text-end fw-semibold">{{ money($registro->total) }}</td>
                            <td>
                                {!! badge($registro->status) !!}
                                @if ($registro->isOverduePartial())
                                    <span class="badge bg-danger ms-1">Parcial vencida</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('purchases.show', $registro) }}" class="btn btn-outline-secondary" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @unless ($registro->isLocked())
                                        <a href="{{ route('purchases.edit', $registro) }}" class="btn btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" title="Eliminar"
                                                data-bs-toggle="modal" data-bs-target="#modalEliminar{{ $registro->id }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="rm-empty">
                                    <i class="bi bi-inbox"></i>
                                    No se encontraron órdenes de compra con los filtros aplicados.
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
        @unless ($registro->isLocked())
            <div class="modal fade" id="modalEliminar{{ $registro->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('purchases.destroy', $registro) }}" class="modal-content">
                        @csrf
                        @method('DELETE')

                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Eliminar orden de compra</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            ¿Seguro que deseas eliminar la orden <strong>{{ $registro->po_number }}</strong>?
                            <div class="small text-muted mt-2">Sus items se eliminarán también.</div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">Eliminar</button>
                        </div>
                    </form>
                </div>
            </div>
        @endunless
    @endforeach
@endsection
