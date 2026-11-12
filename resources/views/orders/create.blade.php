@extends('layouts.app')

@section('title', 'Nueva orden')
@section('breadcrumb', 'Órdenes / Nueva orden')

@section('content')
    <form method="POST" action="{{ route('orders.store') }}">
        @csrf

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card rm-card mb-3">
                    <div class="rm-card-header"><i class="bi bi-receipt me-2"></i>Datos de la orden</div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="source" class="form-label fw-semibold">Origen <span class="text-danger">*</span></label>
                                <select name="source" id="source" class="form-select @error('source') is-invalid @enderror" required>
                                    @foreach ($origenes as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected(old('source', 'dine_in') === $valor)>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                                @error('source')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="restaurant_table_id" class="form-label fw-semibold">Mesa</label>
                                <select name="restaurant_table_id" id="restaurant_table_id" class="form-select @error('restaurant_table_id') is-invalid @enderror">
                                    <option value="">Sin mesa</option>
                                    @foreach ($mesas as $mesa)
                                        <option value="{{ $mesa->id }}"
                                                @selected((string) old('restaurant_table_id') === (string) $mesa->id)
                                                @disabled(! $mesa->isAssignableToOrder())>
                                            {{ $mesa->table_number }} · {{ $mesa->locationEnum()->label() }}
                                            @if (! $mesa->isAssignableToOrder())
                                                ({{ $mesa->status->label() }} - No disponible)
                                            @else
                                                ({{ $mesa->status->label() }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('restaurant_table_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="status" class="form-label fw-semibold">Estado</label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                    @foreach ($estados as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected(old('status', 'open') === $valor)>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="customer_id" class="form-label fw-semibold">Cliente</label>
                                <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror">
                                    <option value="">Sin cliente</option>
                                    @foreach ($clientes as $cliente)
                                        <option value="{{ $cliente->id }}" @selected((string) old('customer_id') === (string) $cliente->id)>{{ $cliente->name }}</option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="employee_id" class="form-label fw-semibold">Empleado</label>
                                <select name="employee_id" id="employee_id" class="form-select @error('employee_id') is-invalid @enderror">
                                    <option value="">Sin empleado</option>
                                    @foreach ($empleados as $empleado)
                                        <option value="{{ $empleado->id }}" @selected((string) old('employee_id') === (string) $empleado->id)>
                                            {{ $empleado->name }} · {{ $empleado->role->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('employee_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="notes" class="form-label fw-semibold">Notas</label>
                                <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror"
                                          placeholder="Indicaciones especiales para la orden...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card rm-card">
                    <div class="rm-card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-list-ul me-2"></i>Items de la orden</span>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnAgregarItem">
                            <i class="bi bi-plus-lg me-1"></i>Añadir item
                        </button>
                    </div>

                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-semibold">Agregar plato rápido</label>
                                <select id="selectorPlato" class="form-select">
                                    <option value="">Selecciona un plato para añadirlo...</option>
                                    @foreach ($platos as $plato)
                                        <option value="{{ $plato->id }}">
                                            {{ $plato->name }} @if ($plato->category) · {{ $plato->category->name }} @endif — {{ money($plato->price) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Al elegir un plato se agrega una fila editable abajo.</div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>El precio unitario se toma del plato.</span>
                            </div>
                        </div>

                        @error('items')
                            <div class="alert alert-warning py-2">{{ $message }}</div>
                        @enderror

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0" id="tablaItems">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 220px;">Plato</th>
                                        <th class="text-end" style="width: 140px;">Precio unit.</th>
                                        <th style="width: 110px;">Cantidad</th>
                                        <th style="min-width: 160px;">Notas</th>
                                        <th class="text-end" style="width: 120px;">Importe</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <tr id="filaVacia">
                                        <td colspan="6">
                                            <div class="rm-empty py-4">
                                                <i class="bi bi-basket"></i>
                                                Aún no has agregado platos a la orden.
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card rm-card mb-3 position-sticky" style="top: 90px;">
                    <div class="rm-card-header"><i class="bi bi-calculator me-2"></i>Resumen</div>
                    <div class="card-body">
                        <dl class="row mb-2">
                            <dt class="col-7 text-muted">Subtotal</dt>
                            <dd class="col-5 text-end" id="resumenSubtotal">{{ money(0) }}</dd>

                            <dt class="col-7 text-muted">IVA incluido ({{ pct(config('restauranager.tax_rate', 0.19) * 100) }})</dt>
                            <dd class="col-5 text-end" id="resumenIva">{{ money(0) }}</dd>

                            <dt class="col-7 fw-bold fs-5">Total</dt>
                            <dd class="col-5 text-end fw-bold fs-5" id="resumenTotal">{{ money(0) }}</dd>
                        </dl>

                        <div class="small text-muted mb-3">
                            <i class="bi bi-info-circle me-1"></i>Los precios del menú incluyen IVA.
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Guardar orden
                            </button>
                            <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    const IVA = {{ (float) config('restauranager.tax_rate', 0.19) }};

    @php
        $platosJson = $platos->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'price' => (float) $p->price,
                'category' => $p->category?->name ?? '',
            ];
        })->values();
    @endphp

    const platos = @json($platosJson);

    const moneyFmt = (v) => '$ ' + Number(v).toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    const priceFmt = (v) => Number(v).toFixed(2);

    let contador = 0;
    const itemsBody = document.getElementById('itemsBody');
    const filaVacia = document.getElementById('filaVacia');
    const selectorPlato = document.getElementById('selectorPlato');

    function actualizarResumen() {
        let total = 0;

        itemsBody.querySelectorAll('tr.item-row').forEach((row) => {
            const precio = parseFloat(row.querySelector('.input-precio').value) || 0;
            const cantidad = parseInt(row.querySelector('.input-cantidad').value) || 0;
            const linea = precio * cantidad;

            row.querySelector('.celda-subtotal').textContent = moneyFmt(linea);
            total += linea;
        });

        const subtotal = Math.round(total / (1 + IVA));
        const iva = total - subtotal;

        document.getElementById('resumenSubtotal').textContent = moneyFmt(subtotal);
        document.getElementById('resumenIva').textContent = moneyFmt(iva);
        document.getElementById('resumenTotal').textContent = moneyFmt(total);

        if (itemsBody.querySelectorAll('tr.item-row').length > 0) {
            filaVacia.style.display = 'none';
        } else {
            filaVacia.style.display = '';
        }
    }

    function agregarFila(platoId, cantidad = 1) {
        const plato = platos.find((p) => String(p.id) === String(platoId));
        if (!plato) return;

        const idx = contador++;

        const tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML = `
            <td>
                <input type="hidden" name="items[${idx}][menu_item_id]" value="${plato.id}">
                <div class="fw-semibold">${plato.name}</div>
                ${plato.category ? `<div class="small text-muted">${plato.category}</div>` : ''}
                <select class="form-select form-select-sm mt-1 select-plato">
                    ${platos.map((p) => `<option value="${p.id}" ${String(p.id) === String(plato.id) ? 'selected' : ''}>${p.name} — ${moneyFmt(p.price)}</option>`).join('')}
                </select>
            </td>
            <td class="text-end">
                <input type="number" class="form-control form-control-sm text-end input-precio" step="0.01" min="0" value="${priceFmt(plato.price)}">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm input-cantidad" name="items[${idx}][quantity]" min="1" step="1" value="${cantidad}" required>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm input-notas" name="items[${idx}][notes]" placeholder="Sin cebolla...">
            </td>
            <td class="text-end fw-semibold celda-subtotal">${moneyFmt(plato.price * cantidad)}</td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-danger btn-quitar" title="Quitar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>
        `;

        itemsBody.appendChild(tr);

        tr.querySelector('.select-plato').addEventListener('change', function () {
            const nuevo = platos.find((p) => String(p.id) === String(this.value));
            if (!nuevo) return;

            tr.querySelector('input[name^="items"][name$="[menu_item_id]"]').value = nuevo.id;
            tr.querySelector('.input-precio').value = priceFmt(nuevo.price);
            tr.querySelector('.fw-semibold').textContent = nuevo.name;
            actualizarResumen();
        });

        tr.querySelector('.input-precio').addEventListener('input', actualizarResumen);
        tr.querySelector('.input-cantidad').addEventListener('input', actualizarResumen);
        tr.querySelector('.btn-quitar').addEventListener('click', function () {
            tr.remove();
            actualizarResumen();
        });

        actualizarResumen();
    }

    selectorPlato.addEventListener('change', function () {
        if (!this.value) return;

        agregarFila(this.value);
        this.value = '';
    });

    document.getElementById('btnAgregarItem').addEventListener('click', function () {
        if (platos.length === 0) return;

        agregarFila(platos[0].id);
    });

    actualizarResumen();
</script>
@endpush
