@php($empleado = $empleado ?? null)

<div class="row g-3">
    <div class="col-md-8">
        <label for="name" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $empleado?->name) }}" placeholder="Ej. Juan Pérez" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="role" class="form-label fw-semibold">Rol <span class="text-danger">*</span></label>
        <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
            <option value="">Selecciona...</option>
            @foreach ($roles as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('role', $empleado?->role?->value) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('role')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="phone" class="form-label fw-semibold">Teléfono</label>
        <input type="text" name="phone" id="phone" maxlength="30"
               class="form-control @error('phone') is-invalid @enderror"
               value="{{ old('phone', $empleado?->phone) }}" placeholder="Ej. 300 123 4567">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="email" class="form-label fw-semibold">Correo electrónico</label>
        <input type="email" name="email" id="email" maxlength="255"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $empleado?->email) }}" placeholder="Ej. juan@restaurante.com">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="document_id" class="form-label fw-semibold">Documento</label>
        <input type="text" name="document_id" id="document_id" maxlength="30"
               class="form-control @error('document_id') is-invalid @enderror"
               value="{{ old('document_id', $empleado?->document_id) }}" placeholder="Ej. 1.023.456.789">
        @error('document_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="position" class="form-label fw-semibold">Cargo</label>
        <input type="text" name="position" id="position" maxlength="255"
               class="form-control @error('position') is-invalid @enderror"
               value="{{ old('position', $empleado?->position) }}" placeholder="Ej. Mesero nocturno">
        @error('position')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="salary" class="form-label fw-semibold">Salario</label>
        <div class="input-group">
            <span class="input-group-text bg-light">$</span>
            <input type="number" name="salary" id="salary" step="0.01" min="0"
                   class="form-control @error('salary') is-invalid @enderror"
                   value="{{ old('salary', $empleado?->salary) }}" placeholder="Ej. 1800000">
        </div>
        @error('salary')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="hired_at" class="form-label fw-semibold">Fecha de contratación</label>
        <input type="date" name="hired_at" id="hired_at"
               class="form-control @error('hired_at') is-invalid @enderror"
               value="{{ old('hired_at', $empleado?->hired_at?->toDateString()) }}">
        @error('hired_at')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <input type="hidden" name="active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="active" value="1" id="active"
                   @checked(old('active', $empleado?->is_active ?? true))>
            <label class="form-check-label" for="active">Empleado activo (puede tener turnos y órdenes asignadas)</label>
        </div>
        @error('active')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>
