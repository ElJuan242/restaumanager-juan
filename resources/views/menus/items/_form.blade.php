@php
    $registro = $registro ?? null;
    $receta = old('recipe', $registro ? $registro->recipeItems->map(fn ($item) => [
        'inventory_id' => $item->inventory_id,
        'quantity' => $item->quantity,
        'unit' => $item->unit,
    ])->all() : []);
    $receta = is_array($receta) ? $receta : [];
    $siguienteIndice = count($receta);
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <label for="name" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $registro?->name) }}" placeholder="Ej. Bandeja paisa, Limonada de coco..." required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="category_id" class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
        <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
            <option value="">Selecciona...</option>
            @foreach ($categorias as $categoria)
                <option value="{{ $categoria->id }}" @selected((int) old('category_id', $registro?->category_id) === $categoria->id)>
                    {{ $categoria->name }}
                </option>
            @endforeach
        </select>
        @error('category_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="price" class="form-label fw-semibold">Precio <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text bg-light">$</span>
            <input type="number" name="price" id="price" step="0.01" min="0"
                   class="form-control @error('price') is-invalid @enderror"
                   value="{{ old('price', $registro?->price ?? 0) }}" required>
        </div>
        @error('price')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="sort_order" class="form-label fw-semibold">Orden <span class="text-danger">*</span></label>
        <input type="number" name="sort_order" id="sort_order" min="0" step="1"
               class="form-control @error('sort_order') is-invalid @enderror"
               value="{{ old('sort_order', $registro?->sort_order ?? 0) }}" required>
        @error('sort_order')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="image" class="form-label fw-semibold">Imagen (ruta o URL)</label>
        <input type="text" name="image" id="image" class="form-control @error('image') is-invalid @enderror"
               value="{{ old('image', $registro?->image) }}" placeholder="Ej. img/aguachile.jpg o https://...">
        @error('image')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="description" class="form-label fw-semibold">Descripción</label>
        <textarea name="description" id="description" rows="2"
                  class="form-control @error('description') is-invalid @enderror"
                  placeholder="Descripción breve del plato o bebida...">{{ old('description', $registro?->description) }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="ingredients" class="form-label fw-semibold">Ingredientes</label>
        <textarea name="ingredients" id="ingredients" rows="2"
                  class="form-control @error('ingredients') is-invalid @enderror"
                  placeholder="totopos, aguacate, queso">{{ old('ingredients', $registro ? implode(', ', $registro->ingredientsList()) : '') }}</textarea>
        <div class="form-text">Separa cada ingrediente con una coma.</div>
        @error('ingredients')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <hr class="my-2">

        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <div>
                <label class="form-label fw-semibold mb-0">Receta por porción</label>
                <div class="form-text mt-0">Insumos que se descuentan del inventario al preparar una porción.</div>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnAgregarReceta">
                <i class="bi bi-plus-lg me-1"></i>Agregar insumo
            </button>
        </div>

        @error('recipe')
            <div class="alert alert-danger py-2 small">{{ $message }}</div>
        @enderror

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Insumo</th>
                        <th style="width: 130px;">Cantidad</th>
                        <th style="width: 100px;">Unidad</th>
                        <th style="width: 44px;"></th>
                    </tr>
                </thead>
                <tbody id="recetaBody">
                    @forelse ($receta as $i => $fila)
                        @php($insumoSeleccionado = $insumos->firstWhere('id', (int) ($fila['inventory_id'] ?? 0)))
                        <tr data-receta-row>
                            <td>
                                <select name="recipe[{{ $i }}][inventory_id]" class="form-select form-select-sm receta-insumo @error('recipe.'.$i.'.inventory_id') is-invalid @enderror">
                                    <option value="">Selecciona insumo...</option>
                                    @foreach ($insumos as $insumo)
                                        <option value="{{ $insumo->id }}" data-unit="{{ $insumo->unit }}"
                                                @selected((int) ($fila['inventory_id'] ?? 0) === $insumo->id)>
                                            {{ $insumo->name }} ({{ $insumo->unit }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('recipe.'.$i.'.inventory_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input type="number" name="recipe[{{ $i }}][quantity]" step="0.01" min="0.01"
                                       class="form-control form-control-sm receta-cantidad @error('recipe.'.$i.'.quantity') is-invalid @enderror"
                                       value="{{ $fila['quantity'] ?? '' }}" required>
                                @error('recipe.'.$i.'.quantity')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </td>
                            <td>
                                <input type="text" name="recipe[{{ $i }}][unit]"
                                       class="form-control form-control-sm bg-light receta-unidad"
                                       value="{{ $fila['unit'] ?? ($insumoSeleccionado?->unit ?? '') }}" readonly>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-outline-danger btn-sm receta-remove" title="Quitar insumo">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr id="recetaEmpty">
                            <td colspan="4" class="text-muted small">
                                Sin receta registrada. Este plato no descuenta inventario al prepararse.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <template id="recetaTemplate">
            <tr data-receta-row>
                <td>
                    <select name="recipe[__INDEX__][inventory_id]" class="form-select form-select-sm receta-insumo">
                        <option value="">Selecciona insumo...</option>
                        @foreach ($insumos as $insumo)
                            <option value="{{ $insumo->id }}" data-unit="{{ $insumo->unit }}">
                                {{ $insumo->name }} ({{ $insumo->unit }})
                            </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" name="recipe[__INDEX__][quantity]" step="0.01" min="0.01"
                           class="form-control form-control-sm receta-cantidad" required>
                </td>
                <td>
                    <input type="text" name="recipe[__INDEX__][unit]"
                           class="form-control form-control-sm bg-light receta-unidad" readonly>
                </td>
                <td class="text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm receta-remove" title="Quitar insumo">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </td>
            </tr>
        </template>
    </div>

    <div class="col-md-6">
        <input type="hidden" name="is_available" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_available" value="1" id="is_available"
                   @checked(old('is_available', $registro?->is_available ?? true))>
            <label class="form-check-label" for="is_available">Disponible en el menú</label>
        </div>
    </div>

    <div class="col-md-6">
        <input type="hidden" name="is_featured" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured"
                   @checked(old('is_featured', $registro?->is_featured ?? false))>
            <label class="form-check-label" for="is_featured">
                <i class="bi bi-star-fill text-warning me-1"></i>Destacado
            </label>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const body = document.getElementById('recetaBody');
            const template = document.getElementById('recetaTemplate');
            const addButton = document.getElementById('btnAgregarReceta');

            if (!body || !template || !addButton) {
                return;
            }

            let nextIndex = {{ $siguienteIndice }};

            const emptyRow = () => `
                <tr id="recetaEmpty">
                    <td colspan="4" class="text-muted small">
                        Sin receta registrada. Este plato no descuenta inventario al prepararse.
                    </td>
                </tr>`;

            const updateUnit = (select) => {
                const row = select.closest('tr');
                const unit = row?.querySelector('.receta-unidad');

                if (!unit) {
                    return;
                }

                unit.value = select.selectedOptions[0]?.dataset.unit || '';
            };

            addButton.addEventListener('click', () => {
                document.getElementById('recetaEmpty')?.remove();

                body.insertAdjacentHTML(
                    'beforeend',
                    template.innerHTML.replaceAll('__INDEX__', nextIndex++)
                );
            });

            body.addEventListener('change', (event) => {
                if (event.target.matches('.receta-insumo')) {
                    updateUnit(event.target);
                }
            });

            body.addEventListener('click', (event) => {
                const removeButton = event.target.closest('.receta-remove');

                if (!removeButton) {
                    return;
                }

                removeButton.closest('tr')?.remove();

                if (!body.querySelector('tr[data-receta-row]')) {
                    body.insertAdjacentHTML('beforeend', emptyRow());
                }
            });

            body.querySelectorAll('.receta-insumo').forEach(updateUnit);
        });
    </script>
@endpush
