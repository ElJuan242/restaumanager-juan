@php
    $registro = $registro ?? null;

    // Items iniciales: los del request (recién validado) o los existentes de la orden.
    $itemsIniciales = old('items');
    if ($itemsIniciales === null && $registro) {
        $itemsIniciales = $registro->items->map(fn ($item) => [
            'inventory_id' => $item->inventory_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
        ])->values()->all();
    }
    $itemsIniciales = $itemsIniciales ?? [];
@endphp

<div class="row g-3">
    <div class="col-md-5">
        <label for="supplier_id" class="form-label fw-semibold">Proveedor <span class="text-danger">*</span></label>
        <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
            <option value="">Selecciona...</option>
            @foreach ($proveedores as $proveedor)
                <option value="{{ $proveedor->id }}" @selected((string) old('supplier_id', $registro?->supplier_id) === (string) $proveedor->id)>
                    {{ $proveedor->name }}
                </option>
            @endforeach
        </select>
        @error('supplier_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="order_date" class="form-label fw-semibold">Fecha de orden <span class="text-danger">*</span></label>
        <input type="date" name="order_date" id="order_date" class="form-control @error('order_date') is-invalid @enderror"
               value="{{ old('order_date', isset($registro) && $registro->order_date ? $registro->order_date->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
        @error('order_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-2">
        <label for="expected_date" class="form-label fw-semibold">Fecha esperada</label>
        <input type="date" name="expected_date" id="expected_date" class="form-control @error('expected_date') is-invalid @enderror"
               value="{{ old('expected_date', isset($registro) && $registro->expected_date ? $registro->expected_date->format('Y-m-d') : '') }}">
        @error('expected_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-2">
        <label for="status" class="form-label fw-semibold">Estado</label>
        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
            @foreach ($estados as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('status', $registro?->status?->value ?? 'pending') === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<hr class="my-4">

<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2"></i>Insumos de la orden</h6>
    <div class="d-flex align-items-end gap-2">
        <div>
            <label class="form-label small fw-semibold mb-1">Añadir insumo</label>
            <select id="selectorInsumo" class="form-select form-select-sm" style="min-width: 260px;">
                <option value="">Selecciona un insumo...</option>
                @foreach ($insumos as $insumo)
                    <option value="{{ $insumo->id }}"
                            data-name="{{ $insumo->name }}"
                            data-unit="{{ $insumo->unit }}"
                            data-price="{{ $insumo->price }}">
                        {{ $insumo->name }} ({{ $insumo->unit }}) · {{ money_exact($insumo->price) }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="button" class="btn btn-sm btn-dark" id="btnAgregarInsumo">
            <i class="bi bi-plus-lg me-1"></i>Agregar
        </button>
    </div>
</div>

@error('items')
    <div class="alert alert-danger py-2 small">{{ $message }}</div>
@enderror
@error('items.*.inventory_id')
    <div class="alert alert-danger py-2 small">{{ $message }}</div>
@enderror
@error('items.*.quantity')
    <div class="alert alert-danger py-2 small">{{ $message }}</div>
@enderror
@error('items.*.unit_price')
    <div class="alert alert-danger py-2 small">{{ $message }}</div>
@enderror

<div class="table-responsive">
    <table class="table table-sm align-middle" id="tablaItems">
        <thead class="table-light">
            <tr>
                <th>Insumo</th>
                <th style="width: 110px;">Unidad</th>
                <th style="width: 130px;" class="text-end">Cantidad</th>
                <th style="width: 150px;" class="text-end">Precio unitario</th>
                <th style="width: 140px;" class="text-end">Subtotal</th>
                <th style="width: 50px;"></th>
            </tr>
        </thead>
        <tbody id="cuerpoItems">
            {{-- Las filas se generan con JavaScript --}}
        </tbody>
    </table>
</div>

<div id="itemsVacio" class="rm-empty d-none">
    <i class="bi bi-cart-plus"></i>
    Aún no has añadido insumos a la orden.
</div>

<div class="row justify-content-end mt-3">
    <div class="col-md-5">
        <table class="table table-sm mb-0">
            <tbody>
                <tr>
                    <td class="text-muted">Subtotal</td>
                    <td class="text-end fw-semibold" id="txtSubtotal">{{ money_exact(0) }}</td>
                </tr>
                <tr>
                    <td class="text-muted">IVA 19%</td>
                    <td class="text-end fw-semibold" id="txtIva">{{ money_exact(0) }}</td>
                </tr>
                <tr class="border-top">
                    <td class="fw-bold">Total</td>
                    <td class="text-end fw-bold fs-5" id="txtTotal">{{ money_exact(0) }}</td>
                </tr>
            </tbody>
        </table>
        <input type="hidden" id="tasaIva" value="0.19">
    </div>
</div>

<div class="mt-3">
    <label for="notes" class="form-label fw-semibold">Notas</label>
    <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror"
              placeholder="Condiciones de entrega, observaciones para el proveedor...">{{ old('notes', $registro?->notes) }}</textarea>
    @error('notes')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

@push('scripts')
<script>
    const catalogo = @json($insumos->mapWithKeys(fn ($i) => [$i->id => ['name' => $i->name, 'unit' => $i->unit, 'price' => (float) $i->price]])->all());
    const itemsIniciales = @json($itemsIniciales);

    const cuerpo = document.getElementById('cuerpoItems');
    const selector = document.getElementById('selectorInsumo');
    const vacio = document.getElementById('itemsVacio');
    let contador = 0;

    function formatoMoneda(valor) {
        return '$ ' + (Math.round(Number(valor) * 100) / 100).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function recalcularTotales() {
        let subtotal = 0;
        cuerpo.querySelectorAll('tr').forEach(function (fila) {
            const cantidad = parseFloat(fila.querySelector('.input-cantidad').value) || 0;
            const precio = parseFloat(fila.querySelector('.input-precio').value) || 0;
            const sub = cantidad * precio;
            fila.querySelector('.celda-subtotal').textContent = formatoMoneda(sub);
            subtotal += sub;
        });

        const tasa = parseFloat(document.getElementById('tasaIva').value) || 0.19;
        const iva = subtotal * tasa;

        document.getElementById('txtSubtotal').textContent = formatoMoneda(subtotal);
        document.getElementById('txtIva').textContent = formatoMoneda(iva);
        document.getElementById('txtTotal').textContent = formatoMoneda(subtotal + iva);

        vacio.classList.toggle('d-none', cuerpo.querySelectorAll('tr').length > 0);
    }

    function crearFila(datos) {
        const info = catalogo[datos.inventory_id];
        if (!info) return;

        const indice = contador++;
        const fila = document.createElement('tr');

        fila.innerHTML = `
            <td class="fw-semibold">${info.name}
                <input type="hidden" name="items[${indice}][inventory_id]" value="${datos.inventory_id}">
            </td>
            <td><span class="badge bg-light text-dark border">${info.unit}</span></td>
            <td class="text-end">
                <input type="number" name="items[${indice}][quantity]" class="form-control form-control-sm text-end input-cantidad"
                       step="0.01" min="0.01" value="${datos.quantity}" required>
            </td>
            <td class="text-end">
                <input type="number" name="items[${indice}][unit_price]" class="form-control form-control-sm text-end input-precio"
                       step="0.01" min="0" value="${datos.unit_price}" required>
            </td>
            <td class="text-end fw-semibold celda-subtotal">${formatoMoneda(0)}</td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-danger btn-quitar" title="Quitar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>
        `;

        fila.querySelector('.input-cantidad').addEventListener('input', recalcularTotales);
        fila.querySelector('.input-precio').addEventListener('input', recalcularTotales);
        fila.querySelector('.btn-quitar').addEventListener('click', function () {
            fila.remove();
            recalcularTotales();
        });

        cuerpo.appendChild(fila);
        recalcularTotales();
    }

    document.getElementById('btnAgregarInsumo').addEventListener('click', function () {
        const id = selector.value;
        if (!id) return;

        const info = catalogo[id];
        crearFila({ inventory_id: id, quantity: 1, unit_price: info.price });
        selector.value = '';
    });

    selector.addEventListener('change', function () {
        if (selector.value) {
            document.getElementById('btnAgregarInsumo').click();
        }
    });

    // Precarga de items existentes o recién validados.
    itemsIniciales.forEach(function (item) {
        crearFila({
            inventory_id: String(item.inventory_id),
            quantity: item.quantity,
            unit_price: item.unit_price,
        });
    });

    recalcularTotales();
</script>
@endpush
