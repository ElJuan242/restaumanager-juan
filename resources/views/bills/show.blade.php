@extends('layouts.app')

@section('title', 'Factura ' . $registro->bill_number)
@section('breadcrumb', 'Facturación / Detalle de la factura')

@section('content')
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card rm-card mb-3">
                <div class="rm-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>
                        <i class="bi bi-cash-stack me-2"></i>{{ $registro->bill_number }}
                        {!! badge($registro->status) !!}
                    </span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-dark" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i>Imprimir
                        </button>
                        <form method="POST" action="{{ route('bills.destroy', $registro) }}"
                              onsubmit="return confirm('¿Seguro que deseas eliminar esta factura?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash me-1"></i>Eliminar
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Orden</dt>
                        <dd class="col-sm-8">
                            @if ($registro->order)
                                <a href="{{ route('orders.show', $registro->order) }}" class="fw-semibold text-decoration-none">
                                    {{ $registro->order->order_number }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted">Cliente</dt>
                        <dd class="col-sm-8">
                            @if ($registro->customer)
                                <a href="{{ route('customers.show', $registro->customer) }}" class="text-decoration-none fw-semibold">
                                    {{ $registro->customer->name }}
                                </a>
                            @else
                                <span class="text-muted">Sin cliente</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted">Mesa</dt>
                        <dd class="col-sm-8">{{ $registro->order?->table?->table_number ?? '—' }}</dd>

                        <dt class="col-sm-4 text-muted">Estado</dt>
                        <dd class="col-sm-8">{!! badge($registro->status) !!}</dd>

                        <dt class="col-sm-4 text-muted">Emitida</dt>
                        <dd class="col-sm-8">{{ date_es($registro->created_at) }} · {{ time_es($registro->created_at) }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card rm-card mb-3">
                <div class="rm-card-header"><i class="bi bi-list-ul me-2"></i>Items de la orden</div>
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
                            @forelse ($registro->order?->items ?? [] as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item->item_name }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">{{ money($item->unit_price) }}</td>
                                    <td class="text-end">{{ money($item->subtotal) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="rm-empty py-3">
                                            <i class="bi bi-basket"></i>
                                            Sin items asociados.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card rm-card">
                <div class="rm-card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-credit-card me-2"></i>Pagos registrados</span>
                    <span class="badge bg-secondary">{{ $registro->payments->count() }}</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Método</th>
                                <th>Referencia</th>
                                <th class="text-end">Monto</th>
                                <th>Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($registro->payments as $pago)
                                <tr>
                                    <td class="text-nowrap small text-muted">
                                        {{ date_es($pago->created_at) }}<br>{{ time_es($pago->created_at) }}
                                    </td>
                                    <td>{!! badge($pago->method) !!}</td>
                                    <td class="small">{{ $pago->reference ?? '—' }}</td>
                                    <td class="text-end fw-semibold">{{ money($pago->amount) }}</td>
                                    <td class="small text-muted">{{ $pago->notes ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="rm-empty py-3">
                                            <i class="bi bi-credit-card"></i>
                                            Todavía no se han registrado pagos.
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
                <div class="rm-card-header"><i class="bi bi-calculator me-2"></i>Importes</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-7 text-muted">Subtotal</dt>
                        <dd class="col-5 text-end">{{ money($registro->subtotal) }}</dd>

                        <dt class="col-7 text-muted">IVA</dt>
                        <dd class="col-5 text-end">{{ money($registro->tax) }}</dd>

                        <dt class="col-7 text-muted">Cargo por servicio</dt>
                        <dd class="col-5 text-end">{{ money($registro->service_charge) }}</dd>

                        <dt class="col-7 text-muted">Descuento</dt>
                        <dd class="col-5 text-end text-danger">- {{ money($registro->discount) }}</dd>

                        <dt class="col-7 fw-bold fs-5">Total</dt>
                        <dd class="col-5 text-end fw-bold fs-5">{{ money($registro->total) }}</dd>

                        <dt class="col-7 text-muted">Pagado</dt>
                        <dd class="col-5 text-end text-success fw-semibold">{{ money($registro->paidAmount()) }}</dd>

                        <dt class="col-7 fw-bold">Saldo</dt>
                        <dd class="col-5 text-end fw-bold {{ $registro->balance() > 0 ? 'text-danger' : 'text-success' }}">
                            {{ money($registro->balance()) }}
                        </dd>
                    </dl>
                </div>
            </div>

            @if (! $registro->isFullyPaid())
                <div class="card rm-card">
                    <div class="rm-card-header"><i class="bi bi-cash-coin me-2"></i>Registrar pago</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('bills.pay', $registro) }}">
                            @csrf

                            <div class="mb-3">
                                <label for="amount" class="form-label fw-semibold">Monto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">$</span>
                                    <input type="number" name="amount" id="amount" step="0.01" min="0.01"
                                           class="form-control @error('amount') is-invalid @enderror"
                                           value="{{ old('amount', $registro->balance()) }}" required>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text">Saldo pendiente: {{ money_exact($registro->balance()) }}.</div>
                            </div>

                            <div class="mb-3">
                                <label for="method" class="form-label fw-semibold">Método de pago <span class="text-danger">*</span></label>
                                <select name="method" id="method" class="form-select @error('method') is-invalid @enderror" required>
                                    @foreach ($metodos as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected(old('method', 'cash') === $valor)>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                                @error('method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3" id="bloqueReferencia" style="display: none;">
                                <label for="reference" class="form-label fw-semibold">Referencia</label>
                                <input type="text" name="reference" id="reference"
                                       class="form-control @error('reference') is-invalid @enderror"
                                       value="{{ old('reference') }}" placeholder="Nº de autorización, comprobante...">
                                @error('reference')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label fw-semibold">Notas</label>
                                <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror"
                                          placeholder="Observaciones del pago...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check2-circle me-1"></i>Registrar pago
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="card rm-card">
                    <div class="card-body text-center">
                        <i class="bi bi-check2-circle text-success" style="font-size: 2.5rem;"></i>
                        <p class="mb-0 fw-semibold">Factura totalmente pagada</p>
                        <div class="small text-muted">No queda saldo pendiente.</div>
                    </div>
                </div>
            @endif

            <div class="mt-3">
                <a href="{{ route('bills.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-left me-1"></i>Volver al listado
                </a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const metodoSelect = document.getElementById('method');
    const bloqueReferencia = document.getElementById('bloqueReferencia');

    const metodosConReferencia = ['card', 'transfer'];

    function actualizarReferencia() {
        if (! metodoSelect || ! bloqueReferencia) return;

        bloqueReferencia.style.display = metodosConReferencia.includes(metodoSelect.value) ? '' : 'none';
    }

    if (metodoSelect) {
        metodoSelect.addEventListener('change', actualizarReferencia);
        actualizarReferencia();
    }
</script>
@endpush
