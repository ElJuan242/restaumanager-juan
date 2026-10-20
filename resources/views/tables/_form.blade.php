@php($registro = $registro ?? null)

<div class="row g-3">
    <div class="col-md-4">
        <label for="table_number" class="form-label fw-semibold">Número de mesa <span class="text-danger">*</span></label>
        <input type="text" name="table_number" id="table_number" class="form-control @error('table_number') is-invalid @enderror"
               value="{{ old('table_number', $registro?->table_number) }}" placeholder="Ej. M-01" maxlength="20" required>
        @error('table_number')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-2">
        <label for="capacity" class="form-label fw-semibold">Capacidad <span class="text-danger">*</span></label>
        <input type="number" name="capacity" id="capacity" min="1" step="1"
               class="form-control @error('capacity') is-invalid @enderror"
               value="{{ old('capacity', $registro?->capacity ?? 1) }}" required>
        @error('capacity')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="location" class="form-label fw-semibold">Ubicación <span class="text-danger">*</span></label>
        <select name="location" id="location" class="form-select @error('location') is-invalid @enderror" required>
            <option value="">Selecciona...</option>
            @foreach ($ubicaciones as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('location', $registro?->location) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('location')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="status" class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
            <option value="">Selecciona...</option>
            @foreach ($estados as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('status', $registro?->status?->value ?? 'available') === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                   @checked(old('is_active', $registro?->is_active ?? true))>
            <label class="form-check-label" for="is_active">Mesa activa (disponible para reservas y órdenes)</label>
        </div>
        @error('is_active')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>
