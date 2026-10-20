@php($cliente = $cliente ?? null)

<div class="row g-3">
    <div class="col-md-8">
        <label for="name" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $cliente?->name) }}" placeholder="Ej. María González" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="phone" class="form-label fw-semibold">Teléfono</label>
        <input type="text" name="phone" id="phone" maxlength="30"
               class="form-control @error('phone') is-invalid @enderror"
               value="{{ old('phone', $cliente?->phone) }}" placeholder="Ej. 300 123 4567">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label fw-semibold">Correo electrónico</label>
        <input type="email" name="email" id="email" maxlength="255"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $cliente?->email) }}" placeholder="Ej. maria@correo.com">
        <div class="form-text">Debe ser único si se registra; se usa para vincular sus reservas.</div>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="address" class="form-label fw-semibold">Dirección</label>
        <input type="text" name="address" id="address" maxlength="255"
               class="form-control @error('address') is-invalid @enderror"
               value="{{ old('address', $cliente?->address) }}" placeholder="Ej. Cra 15 # 42-08">
        @error('address')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="birth_date" class="form-label fw-semibold">Fecha de nacimiento</label>
        <input type="date" name="birth_date" id="birth_date"
               class="form-control @error('birth_date') is-invalid @enderror"
               value="{{ old('birth_date', $cliente?->birth_date?->toDateString()) }}">
        @error('birth_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label fw-semibold">Notas</label>
        <textarea name="notes" id="notes" rows="2"
                  class="form-control @error('notes') is-invalid @enderror"
                  placeholder="Preferencias, alergias, mesas favoritas, observaciones...">{{ old('notes', $cliente?->notes) }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <input type="hidden" name="is_vip" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_vip" value="1" id="is_vip"
                   @checked(old('is_vip', $cliente?->is_vip ?? false))>
            <label class="form-check-label" for="is_vip">
                <i class="bi bi-star-fill text-warning me-1"></i>Cliente VIP (trato preferente y badge dorado)
            </label>
        </div>
        @error('is_vip')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>
