@extends('layouts.app')

@section('title', 'Nueva factura')
@section('breadcrumb', 'Facturación / Nueva factura')

@section('content')
    @if ($ordenSel)
        @php
            $subtotal = (float) $ordenSel->subtotal;
            $tax = (float) $ordenSel->tax;
            $servicioSugerido = round($subtotal * 0.10, 2);
        @endphp

        <form method="POST" action="{{ route('bills.store') }}">
            @csrf
            <input type="hidden" name="order_id" value="{{ $ordenSel->id }}">

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card rm-card mb-3">
                        <div class="rm-card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-receipt me-2"></i>Orden {{ $ordenSel->order_number }}</span>
                            {!! badge($ordenSel->status) !!}
                        </div>

                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4 text-muted">Cliente</dt>
                                <dd class="col-sm-8">{{ $ordenSel->customer?->name ?? 'Sin cliente' }}</dd>

                                <dt class="col-sm-4 text-muted">Mesa</dt>
                                <dd class="col-sm-8">{{ $ordenSel->table?->table_number ?? '—' }}</dd>

                                <dt class="col-sm-4 text-muted">Origen</dt>
                                <dd class="col-sm-8">{!! badge($ordenSel->source) !!}</dd>

                                <dt class="col-sm-4 text-muted">Fecha</dt>
                                <dd class="col-sm-8">{{ date_es($ordenSel->created_at) }} · {{ time_es($ordenSel->created_at) }}</dd>
                            </dl>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Plato</th>
                                        <th class="text-center">Cant.</th>
                                        <th class="text-end">Precio unit.</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ordenSel->items as $item)
                                        <tr>
                                            <td class="fw-semibold">{{ $item->item_name }}</td>
                                            <td class="text-center">{{ $item->quantity }}</td>
                                            <td class="text-end">{{ money($item->unit_price) }}</td>
                                            <td class="text-end">{{ money($item->subtotal) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card rm-card mb-3">
                        <div class="rm-card-header"><i class="bi bi-calculator me-2"></i>Ajustes de la factura</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="service_charge" class="form-label fw-semibold">Cargo por servicio (10%)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">$</span>
                                    <input type="number" name="service_charge" id="service_charge" step="0.01" min="0"
                                           class="form-control @error('service_charge') is-invalid @enderror"
                                           value="{{ old('service_charge', $servicioSugerido) }}">
                                    @error('service_charge')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text">Por defecto el 10% del subtotal.</div>
                            </div>

                            <div class="mb-3">
                                <label for="discount" class="form-label fw-semibold">Descuento</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">$</span>
                                    <input type="number" name="discount" id="discount" step="0.01" min="0"
                                           class="form-control @error('discount') is-invalid @enderror"
                                           value="{{ old('discount', 0) }}">
                                    @error('discount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <dl class="row mb-2">
                                <dt class="col-7 text-muted">Subtotal</dt>
                                <dd class="col-5 text-end" id="creSubtotal">{{ money($subtotal) }}</dd>

                                <dt class="col-7 text-muted">IVA ({{ pct(19) }})</dt>
                                <dd class="col-5 text-end" id="creIva">{{ money($tax) }}</dd>

                                <dt class="col-7 text-muted">Cargo servicio</dt>
                                <dd class="col-5 text-end" id="creServicio">{{ money($servicioSugerido) }}</dd>

                                <dt class="col-7 text-muted">Descuento</dt>
                                <dd class="col-5 text-end text-danger" id="creDescuento">{{ money(0) }}</dd>

                                <dt class="col-7 fw-bold fs-5">Total</dt>
                                <dd class="col-5 text-end fw-bold fs-5" id="creTotal">{{ money($subtotal + $tax + $servicioSugerido) }}</dd>
                            </dl>
                        </div>

                        <div class="card-footer bg-white d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Generar factura
                            </button>
                            <a href="{{ route('bills.create') }}" class="btn btn-outline-secondary">Elegir otra orden</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @else
        <div class="card rm-card">
            <div class="rm-card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-ul me-2"></i>Órdenes sin factura</span>
                <span class="badge bg-secondary">{{ $ordenes->count() }}</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Orden</th>
                            <th>Cliente</th>
                            <th>Mesa</th>
                            <th class="text-center">Items</th>
                            <th>Estado</th>
                            <th class="text-end">Total</th>
                            <th>Fecha</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ordenes as $orden)
                            <tr>
                                <td class="fw-semibold">{{ $orden->order_number }}</td>
                                <td>{{ $orden->customer?->name ?? '—' }}</td>
                                <td>{{ $orden->table?->table_number ?? '—' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">{{ $orden->items->count() }}</span>
                                </td>
                                <td>{!! badge($orden->status) !!}</td>
                                <td class="text-end fw-semibold">{{ money($orden->total) }}</td>
                                <td class="text-nowrap small text-muted">{{ date_es($orden->created_at) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('bills.create', ['order_id' => $orden->id]) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-cash-stack me-1"></i>Facturar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="rm-empty">
                                        <i class="bi bi-inbox"></i>
                                        No hay órdenes pendientes por facturar.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@if ($ordenSel)
    @push('scripts')
    <script>
        const creSubtotal = {{ (float) $ordenSel->subtotal }};
        const creIva = {{ (float) $ordenSel->tax }};

        const moneyFmtCre = (v) => '$ ' + Number(v).toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

        const inputServicio = document.getElementById('service_charge');
        const inputDescuento = document.getElementById('discount');

        function recalcularFactura() {
            const servicio = parseFloat(inputServicio.value) || 0;
            const descuento = parseFloat(inputDescuento.value) || 0;

            const total = creSubtotal + creIva + servicio - descuento;

            document.getElementById('creServicio').textContent = moneyFmtCre(servicio);
            document.getElementById('creDescuento').textContent = moneyFmtCre(descuento);
            document.getElementById('creTotal').textContent = moneyFmtCre(total);
        }

        inputServicio.addEventListener('input', recalcularFactura);
        inputDescuento.addEventListener('input', recalcularFactura);

        recalcularFactura();
    </script>
    @endpush
@endif
