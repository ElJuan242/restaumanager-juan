@extends('layouts.app')

@section('title', 'Facturación')
@section('breadcrumb', 'Facturas y pagos')

@section('content')
    <div class="card rm-card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('bills.index') }}" class="row g-2 align-items-end">
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
                    <label class="form-label small fw-semibold">Desde</label>
                    <input type="date" name="from" value="{{ $filtros['from'] ?? '' }}" class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Hasta</label>
                    <input type="date" name="to" value="{{ $filtros['to'] ?? '' }}" class="form-control">
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark flex-fill">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="{{ route('bills.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card rm-card">
        <div class="rm-card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-cash-stack me-2"></i>Facturas <span class="badge bg-secondary">{{ $registros->total() }}</span></span>
            <a href="{{ route('bills.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nueva factura
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nº factura</th>
                        <th>Orden</th>
                        <th>Cliente</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">IVA</th>
                        <th class="text-end">Servicio</th>
                        <th class="text-end">Descuento</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Pagado</th>
                        <th class="text-end">Saldo</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registros as $registro)
                        <tr>
                            <td>
                                <a href="{{ route('bills.show', $registro) }}" class="fw-semibold text-decoration-none">
                                    {{ $registro->bill_number }}
                                </a>
                            </td>
                            <td>
                                @if ($registro->order)
                                    <a href="{{ route('orders.show', $registro->order) }}" class="text-decoration-none">
                                        {{ $registro->order->order_number }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $registro->customer?->name ?? '—' }}</td>
                            <td class="text-end">{{ money($registro->subtotal) }}</td>
                            <td class="text-end">{{ money($registro->tax) }}</td>
                            <td class="text-end">{{ money($registro->service_charge) }}</td>
                            <td class="text-end">{{ money($registro->discount) }}</td>
                            <td class="text-end fw-semibold">{{ money($registro->total) }}</td>
                            <td class="text-end text-success">{{ money($registro->paidAmount()) }}</td>
                            <td class="text-end {{ $registro->balance() > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                {{ money($registro->balance()) }}
                            </td>
                            <td>{!! badge($registro->status) !!}</td>
                            <td class="text-nowrap small text-muted">
                                {{ date_es($registro->created_at) }}<br>{{ time_es($registro->created_at) }}
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('bills.show', $registro) }}" class="btn btn-outline-secondary" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
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
                            <td colspan="13">
                                <div class="rm-empty">
                                    <i class="bi bi-inbox"></i>
                                    No se encontraron facturas con los filtros aplicados.
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
                <form method="POST" action="{{ route('bills.destroy', $registro) }}" class="modal-content">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Eliminar factura</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        ¿Seguro que deseas eliminar la factura <strong>{{ $registro->bill_number }}</strong>?
                        <div class="small text-muted mt-2">
                            No será posible eliminarla si tiene pagos registrados.
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
