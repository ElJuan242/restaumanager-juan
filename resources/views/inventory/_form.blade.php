@php($registro = $registro ?? null)

<div class="row g-3">
    <div class="col-md-8">
        <label for="name" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $registro?->name) }}" placeholder="Ej. Pechuga de pollo" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="category" class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
        <select name="category" id="category" class="form-select @error('category') is-invalid @enderror" required>
            <option value="">Selecciona...</option>
            @foreach ($categorias as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('category', $registro?->category) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('category')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="description" class="form-label fw-semibold">Descripción</label>
        <textarea name="description" id="description" rows="2"
                  class="form-control @error('description') is-invalid @enderror"
                  placeholder="Notas, proveedor habitual, condiciones de almacenamiento...">{{ old('description', $registro?->description) }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="quantity" class="form-label fw-semibold">Cantidad <span class="text-danger">*</span></label>
        <input type="number" name="quantity" id="quantity" step="0.01" min="0"
               class="form-control @error('quantity') is-invalid @enderror"
               value="{{ old('quantity', $registro?->quantity ?? 0) }}" required>
        @error('quantity')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="unit" class="form-label fw-semibold">Unidad <span class="text-danger">*</span></label>
        <select name="unit" id="unit" class="form-select @error('unit') is-invalid @enderror" required>
            @foreach ($unidades as $unidad)
                <option value="{{ $unidad }}" @selected(old('unit', $registro?->unit ?? 'und') === $unidad)>{{ $unidad }}</option>
            @endforeach
        </select>
        @error('unit')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="price" class="form-label fw-semibold">Precio unitario <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text bg-light">$</span>
            <input type="number" name="price" id="price" step="0.01" min="0"
                   class="form-control @error('price') is-invalid @enderror"
                   value="{{ old('price', $registro?->price ?? 0) }}" required>
            @error('price')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <label for="min_stock" class="form-label fw-semibold">Stock mínimo <span class="text-danger">*</span></label>
        <input type="number" name="min_stock" id="min_stock" step="0.01" min="0"
               class="form-control @error('min_stock') is-invalid @enderror"
               value="{{ old('min_stock', $registro?->min_stock ?? 0) }}" required>
        @error('min_stock')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <input type="hidden" name="active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="active" value="1" id="active"
                   @checked(old('active', $registro?->active ?? true))>
            <label class="form-check-label" for="active">Producto activo (visible y con movimiento permitido)</label>
        </div>
    </div>
</div>
