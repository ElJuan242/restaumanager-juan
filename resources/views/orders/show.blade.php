@extends('layouts.app')

@section('title', 'Orden ' . $registro->order_number)
@section('breadcrumb', 'Órdenes / Detalle de la orden')

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card rm-card mb-3">
                <div class="rm-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>
                        <i class="bi bi-receipt-cutoff me-2"></i>{{ $registro->order_number }}
                        {!! badge($registro->status) !!}
                        {!! badge($registro->source) !!}
                    </span>
                    <div class="d-flex gap-2">
                        <a href="{{ route('orders.edit', $registro) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil me-1"></i>Editar
                        </a>
                        <form method="POST" action="{{ route('orders.destroy', $registro) }}"
                              onsubmit="return confirm('¿Seguro que deseas eliminar esta orden?');">
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
                        <dt class="col-sm-4 text-muted">Origen</dt>
                        <dd class="col-sm-8">{!! badge($registro->source) !!}</dd>

                        <dt class="col-sm-4 text-muted">Estado</dt>
                        <dd class="col-sm-8">{!! badge($registro->status) !!}</dd>

                        <dt class="col-sm-4 text-muted">Notas</dt>
                        <dd class="col-sm-8">{{ $registro->notes ?? '—' }}</dd>

                        <dt class="col-sm-4 text-muted">Creada</dt>
                        <dd class="col-sm-8">{{ date_es($registro->created_at) }} · {{ time_es($registro->created_at) }}</dd>

                        <dt class="col-sm-4 text-muted">Factura</dt>
                        <dd class="col-sm-8">
                            @if ($registro->bill)
                                <a href="{{ route('bills.show', $registro->bill) }}" class="fw-semibold text-decoration-none">
                                    {{ $registro->bill->bill_number }}
                                </a>
                                {!! badge($registro->bill->status) !!}
                            @else
                                <span class="text-muted">Sin factura generada</span>
                            @endif
                        </dd>
                    </dl>
                </div>

                <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Volver al listado
                    </a>

                    <div class="d-flex gap-2 flex-wrap">
                        @if (! $registro->bill)
                            <a href="{{ route('bills.create', ['order_id' => $registro->id]) }}" class="btn btn-success btn-sm">
                                <i class="bi bi-cash-stack me-1"></i>Generar factura
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card rm-card mb-3">
                <div class="rm-card-header"><i class="bi bi-list-check me-2"></i>Items y estado individual</div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Plato</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-end">Precio unit.</th>
                                <th class="text-end">Subtotal</th>
                                <th style="min-width: 220px;">Estado del item</th>
                                <th>Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($registro->items as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $item->item_name }}</div>
                                        @if ($item->menuItem)
                                            <div class="small text-muted">{{ $item->menuItem->name }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">{{ money($item->unit_price) }}</td>
                                    <td class="text-end fw-semibold">{{ money($item->subtotal) }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('orders.item-status', $item) }}">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                @foreach ($estadosItem as $valor => $etiqueta)
                                                    <option value="{{ $valor }}" @selected($item->status->value === $valor)>{{ $etiqueta }}</option>
                                                @endforeach
                                            </select>
                                            <div class="mt-1">{!! badge($item->status) !!}</div>
                                        </form>
                                    </td>
                                    <td class="small text-muted">{{ $item->notes ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="rm-empty">
                                            <i class="bi bi-basket"></i>
                                            Esta orden todavía no tiene items.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($registro->isActive())
                <div class="card rm-card">
                    <div class="rm-card-header"><i class="bi bi-plus-circle me-2"></i>Añadir items</div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('orders.add-items', $registro) }}" id="formAddItems">
                            @csrf

                            @error('items')
                                <div class="alert alert-warning py-2">{{ $message }}</div>
                            @enderror

                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label class="form-label small fw-semibold">Agregar plato rápido</label>
                                    <select id="selectorPlatoAnadir" class="form-select">
                                        <option value="">Selecciona un plato para añadirlo...</option>
                                        @foreach ($platos as $plato)
                                            <option value="{{ $plato->id }}">
                                                {{ $plato->name }} — {{ money($plato->price) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-primary w-100" id="btnAgregarItemAnadir">
                                        <i class="bi bi-plus-lg me-1"></i>Añadir fila
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-3" id="tablaItemsAnadir">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="min-width: 220px;">Plato</th>
                                            <th class="text-end" style="width: 140px;">Precio unit.</th>
                                            <th style="width: 110px;">Cantidad</th>
                                            <th style="min-width: 160px;">Notas</th>
                                            <th class="text-end" style="width: 120px;">Subtotal</th>
                                            <th style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsBodyAnadir">
                                        <tr id="filaVaciaAnadir">
                                            <td colspan="6">
                                                <div class="rm-empty py-4">
                                                    <i class="bi bi-basket"></i>
                                                    Aún no has agregado platos nuevos.
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i>Guardar items
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card rm-card mb-3">
                <div class="rm-card-header"><i class="bi bi-calculator me-2"></i>Totales</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-7 text-muted">Subtotal</dt>
                        <dd class="col-5 text-end">{{ money($registro->subtotal) }}</dd>

                        <dt class="col-7 text-muted">IVA ({{ pct(19) }})</dt>
                        <dd class="col-5 text-end">{{ money($registro->tax) }}</dd>

                        <dt class="col-7 fw-bold fs-5">Total</dt>
                        <dd class="col-5 text-end fw-bold fs-5">{{ money($registro->total) }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card rm-card mb-3">
                <div class="rm-card-header"><i class="bi bi-info-circle me-2"></i>Datos de la orden</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5 text-muted">Mesa</dt>
                        <dd class="col-7 text-end">
                            @if ($registro->table)
                                <a href="{{ route('tables.show', $registro->table) }}" class="text-decoration-none fw-semibold">
                                    {{ $registro->table->table_number }}
                                </a>
                                <div class="small text-muted">{{ $registro->table->locationEnum()->label() }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </dd>

                        <dt class="col-5 text-muted">Cliente</dt>
                        <dd class="col-7 text-end">
                            @if ($registro->customer)
                                <a href="{{ route('customers.show', $registro->customer) }}" class="text-decoration-none fw-semibold">
                                    {{ $registro->customer->name }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </dd>

                        <dt class="col-5 text-muted">Empleado</dt>
                        <dd class="col-7 text-end">
                            @if ($registro->employee)
                                <a href="{{ route('employees.show', $registro->employee) }}" class="text-decoration-none fw-semibold">
                                    {{ $registro->employee->name }}
                                </a>
                                <div class="small text-muted">{{ $registro->employee->role->label() }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </dd>

                        <dt class="col-5 text-muted">Items</dt>
                        <dd class="col-7 text-end fw-semibold">{{ $registro->items->count() }}</dd>
                    </dl>
                </div>

                <div class="card-footer bg-white">
                    <form method="POST" action="{{ route('orders.update', $registro) }}">
                        @csrf
                        @method('PUT')

                        <input type="hidden" name="source" value="{{ $registro->source->value }}">
                        <input type="hidden" name="restaurant_table_id" value="{{ $registro->restaurant_table_id }}">
                        <input type="hidden" name="customer_id" value="{{ $registro->customer_id }}">
                        <input type="hidden" name="employee_id" value="{{ $registro->employee_id }}">
                        <input type="hidden" name="notes" value="{{ $registro->notes }}">

                        <label for="statusShow" class="small fw-semibold">Cambiar estado de la orden</label>
                        <div class="d-flex gap-2 mt-1">
                            <select name="status" id="statusShow" class="form-select form-select-sm">
                                @foreach ($estados as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @selected($registro->status->value === $valor)>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-dark btn-sm">
                                <i class="bi bi-check2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@if ($registro->isActive())
    @push('scripts')
    <script>
        const IVA_ADD = 0.19;

        @php
            $platosAddJson = $platos->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => (float) $p->price,
                ];
            })->values();
        @endphp

        const platosAdd = @json($platosAddJson);

        const moneyFmtAdd = (v) => '$ ' + Number(v).toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        const priceFmtAdd = (v) => Number(v).toFixed(2);

        let contadorAdd = 0;
        const itemsBodyAdd = document.getElementById('itemsBodyAnadir');
        const filaVaciaAdd = document.getElementById('filaVaciaAnadir');
        const selectorPlatoAdd = document.getElementById('selectorPlatoAnadir');

        function actualizarVacioAdd() {
            if (itemsBodyAdd.querySelectorAll('tr.item-row').length > 0) {
                filaVaciaAdd.style.display = 'none';
            } else {
                filaVaciaAdd.style.display = '';
            }
        }

        function agregarFilaAdd(platoId, cantidad = 1) {
            const plato = platosAdd.find((p) => String(p.id) === String(platoId));
            if (!plato) return;

            const idx = contadorAdd++;

            const tr = document.createElement('tr');
            tr.className = 'item-row';
            tr.innerHTML = `
                <td>
                    <input type="hidden" name="items[${idx}][menu_item_id]" value="${plato.id}">
                    <div class="fw-semibold">${plato.name}</div>
                </td>
                <td class="text-end">
                    <input type="number" class="form-control form-control-sm text-end input-precio-add" step="0.01" min="0" value="${priceFmtAdd(plato.price)}" readonly>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm input-cantidad-add" name="items[${idx}][quantity]" min="1" step="1" value="${cantidad}" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="items[${idx}][notes]" placeholder="Sin cebolla...">
                </td>
                <td class="text-end fw-semibold celda-subtotal-add">${moneyFmtAdd(plato.price * cantidad)}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-quitar-add" title="Quitar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </td>
            `;

            itemsBodyAdd.appendChild(tr);

            const actualizarLinea = function () {
                const precio = parseFloat(tr.querySelector('.input-precio-add').value) || 0;
                const cant = parseInt(tr.querySelector('.input-cantidad-add').value) || 0;
                tr.querySelector('.celda-subtotal-add').textContent = moneyFmtAdd(precio * cant);
            };

            tr.querySelector('.input-cantidad-add').addEventListener('input', actualizarLinea);
            tr.querySelector('.btn-quitar-add').addEventListener('click', function () {
                tr.remove();
                actualizarVacioAdd();
            });

            actualizarVacioAdd();
        }

        selectorPlatoAdd.addEventListener('change', function () {
            if (!this.value) return;

            agregarFilaAdd(this.value);
            this.value = '';
        });

        document.getElementById('btnAgregarItemAnadir').addEventListener('click', function () {
            if (platosAdd.length === 0) return;

            agregarFilaAdd(platosAdd[0].id);
        });
    </script>
    @endpush
@endif
