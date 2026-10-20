@php($registro = $registro ?? null)

<div class="row g-3">
    <div class="col-md-8">
        <label for="name" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $registro?->name) }}" placeholder="Ej. Distribuidora La Cosecha" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="category" class="form-label fw-semibold">Categoría de suministro</label>
        <select name="category" id="category" class="form-select @error('category') is-invalid @enderror">
            <option value="">Sin categoría</option>
            @foreach ($categorias as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('category', $registro?->category) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('category')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="contact_name" class="form-label fw-semibold">Persona de contacto</label>
        <input type="text" name="contact_name" id="contact_name" class="form-control @error('contact_name') is-invalid @enderror"
               value="{{ old('contact_name', $registro?->contact_name) }}" placeholder="Ej. Juan Gómez" maxlength="255">
        @error('contact_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="phone" class="form-label fw-semibold">Teléfono</label>
        <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror"
               value="{{ old('phone', $registro?->phone) }}" placeholder="Ej. 300 123 4567" maxlength="30">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="email" class="form-label fw-semibold">Correo electrónico</label>
        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $registro?->email) }}" placeholder="Ej. ventas@proveedor.com" maxlength="255">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="address" class="form-label fw-semibold">Dirección</label>
        <input type="text" name="address" id="address" class="form-control @error('address') is-invalid @enderror"
               value="{{ old('address', $registro?->address) }}" placeholder="Ej. Calle 45 #12-30, Bodega 4" maxlength="255">
        @error('address')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label fw-semibold">Notas</label>
        <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror"
                  placeholder="Condiciones de pago, días de entrega, observaciones...">{{ old('notes', $registro?->notes) }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <input type="hidden" name="active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="active" value="1" id="active"
                   @checked(old('active', $registro?->is_active ?? true))>
            <label class="form-check-label" for="active">Proveedor activo (disponible para órdenes de compra)</label>
        </div>
    </div>
</div>
