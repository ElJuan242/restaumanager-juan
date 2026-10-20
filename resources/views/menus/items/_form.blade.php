@php($registro = $registro ?? null)

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
