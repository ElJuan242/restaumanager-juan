@extends('layouts.app')

@section('title', $orden->po_number)
@section('breadcrumb', 'Órdenes de compra / Detalle de la orden')

@section('content')
    @if ($orden->isLocked())
        <div class="alert alert-secondary d-flex align-items-center">
            <i class="bi bi-lock me-2"></i>
            Esta orden está {{ $orden->status->label() }}: ya no se puede editar ni eliminar.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card rm-card mb-3">
                <div class="rm-card-header d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-cart-check me-2"></i>{{ $orden->po_number }}
                        {!! badge($orden->status) !!}
                    </span>
                    <div class="d-flex gap-2">
                        @unless ($orden->isLocked())
                            <a href="{{ route('purchases.edit', $orden) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil me-1"></i>Editar
                            </a>
                            <form method="POST" action="{{ route('purchases.destroy', $orden) }}"
                                  onsubmit="return confirm('¿Seguro que deseas eliminar esta orden de compra?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash me-1"></i>Eliminar
                                </button>
                            </form>
                        @endunless
                    </div>
                </div>

                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Proveedor</dt>
                        <dd class="col-sm-8">
                            @if ($orden->supplier)
                                <a href="{{ route('suppliers.show', $orden->supplier) }}" class="fw-semibold text-decoration-none">
                                    {{ $orden->supplier->name }}
                                </a>
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted">Fecha de orden</dt>
                        <dd class="col-sm-8">{{ date_es($orden->order_date) }}</dd>

                        <dt class="col-sm-4 text-muted">Fecha esperada</dt>
                        <dd class="col-sm-8">{{ date_es($orden->expected_date) }}</dd>

                        <dt class="col-sm-4 text-muted">Fecha de recepción</dt>
                        <dd class="col-sm-8">{{ date_es($orden->received_date) }}</dd>

                        <dt class="col-sm-4 text-muted">Estado</dt>
                        <dd class="col-sm-8">{!! badge($orden->status) !!}</dd>

                        <dt class="col-sm-4 text-muted">Registrada</dt>
                        <dd class="col-sm-8">{{ date_es($orden->created_at) }}</dd>

                        <dt class="col-sm-4 text-muted">Notas</dt>
                        <dd class="col-sm-8">{{ $orden->notes ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card rm-card">
                <div class="rm-card-header"><i class="bi bi-list-ul me-2"></i>Insumos de la orden</div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Insumo</th>
                                <th class="text-end">Pedido</th>
                                <th class="text-end">Recibido</th>
                                <th class="text-end">Pendiente</th>
                                <th class="text-end">Precio</th>
                                <th class="text-end">Subtotal</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orden->items as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item->item_name }}</div>
                                        <div class="small text-muted">{{ $item->unit }}</div>
                                    </td>
                                    <td class="text-end">{{ rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',') }}</td>
                                    <td class="text-end">{{ rtrim(rtrim(number_format($item->received_qty, 2, ',', '.'), '0'), ',') }}</td>
                                    <td class="text-end fw-semibold">{{ rtrim(rtrim(number_format($item->pendingQty(), 2, ',', '.'), '0'), ',') }}</td>
                                    <td class="text-end">{{ money_exact($item->unit_price) }}</td>
                                    <td class="text-end">{{ money($item->subtotal) }}</td>
                                    <td>
                                        @if ($item->isFullyReceived())
                                            <span class="badge bg-success">Recibido completo</span>
                                        @elseif ($item->received_qty > 0)
                                            <span class="badge bg-info text-dark">Parcial</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="rm-empty">
                                            <i class="bi bi-inbox"></i>
                                            Esta orden no tiene items registrados.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card rm-card mb-3">
                <div class="rm-card-header"><i class="bi bi-calculator me-2"></i>Totales</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal</span>
                        <span class="fw-semibold">{{ money_exact($orden->subtotal) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">IVA 19%</span>
                        <span class="fw-semibold">{{ money_exact($orden->tax) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold fs-4">{{ money_exact($orden->total) }}</span>
                    </div>
                </div>
            </div>

            @unless ($orden->isLocked())
                <div class="card rm-card">
                    <div class="rm-card-header"><i class="bi bi-box-arrow-in-down me-2"></i>Registrar recepción de mercancía</div>

                    <form method="POST" action="{{ route('purchases.receive', $orden) }}">
                        @csrf

                        <div class="card-body">
                            <div class="mb-3">
                                <label for="received_date" class="form-label fw-semibold">Fecha de recepción</label>
                                <input type="date" name="received_date" id="received_date"
                                       class="form-control @error('received_date') is-invalid @enderror"
                                       value="{{ old('received_date', now()->format('Y-m-d')) }}">
                                @error('received_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if ($orden->items->isEmpty())
                                <div class="rm-empty">
                                    <i class="bi bi-inbox"></i>
                                    No hay items para recibir.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Insumo</th>
                                                <th class="text-end">Pendiente</th>
                                                <th style="width: 130px;" class="text-end">Recibir</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($orden->items as $item)
                                                <tr>
                                                    <td class="small fw-semibold">
                                                        {{ $item->item_name }}
                                                        <div class="text-muted">{{ $item->unit }}</div>
                                                    </td>
                                                    <td class="text-end small">
                                                        {{ rtrim(rtrim(number_format($item->pendingQty(), 2, ',', '.'), '0'), ',') }}
                                                    </td>
                                                    <td class="text-end">
                                                        <input type="number" name="received[{{ $item->id }}]"
                                                               class="form-control form-control-sm text-end"
                                                               min="0" max="{{ $item->pendingQty() }}" step="0.01"
                                                               value="{{ number_format($item->pendingQty(), 2, '.', '') }}"
                                                               @disabled($item->isFullyReceived())>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="form-text mt-2">
                                    Al registrar la recepción, el stock del inventario se incrementa automáticamente por cada cantidad recibida.
                                </div>
                            @endif
                        </div>

                        <div class="card-footer bg-white">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check2-circle me-1"></i>Registrar recepción
                            </button>
                        </div>
                    </form>
                </div>
            @endunless
        </div>
    </div>
@endsection
