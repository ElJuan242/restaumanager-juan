@php($registro = $registro ?? null)

<div class="row g-3">
    <div class="col-md-8">
        <label for="name" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $registro?->name) }}" placeholder="Ej. Entrantes, Fuertes, Bebidas..." required>
        @error('name')
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
        <div class="form-text">Define el orden de aparición en el menú.</div>
    </div>

    <div class="col-12">
        <label for="description" class="form-label fw-semibold">Descripción</label>
        <textarea name="description" id="description" rows="2"
                  class="form-control @error('description') is-invalid @enderror"
                  placeholder="Breve descripción de la categoría...">{{ old('description', $registro?->description) }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <input type="hidden" name="active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="active" value="1" id="active"
                   @checked(old('active', $registro?->is_active ?? true))>
            <label class="form-check-label" for="active">Categoría activa (visible en el menú)</label>
        </div>
    </div>
</div>
