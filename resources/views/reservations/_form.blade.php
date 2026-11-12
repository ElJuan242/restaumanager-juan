@php($registro = $registro ?? null)

<div class="row g-3">
    <div class="col-md-6">
        <label for="client_name" class="form-label fw-semibold">Nombre del cliente <span class="text-danger">*</span></label>
        <input type="text" name="client_name" id="client_name" class="form-control @error('client_name') is-invalid @enderror"
               value="{{ old('client_name', $registro?->client_name) }}" placeholder="Ej. María Pérez" required>
        @error('client_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="client_phone" class="form-label fw-semibold">Teléfono</label>
        <input type="text" name="client_phone" id="client_phone" class="form-control @error('client_phone') is-invalid @enderror"
               value="{{ old('client_phone', $registro?->client_phone) }}" placeholder="Ej. 300 123 4567" maxlength="30">
        @error('client_phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="client_email" class="form-label fw-semibold">Correo electrónico</label>
        <input type="email" name="client_email" id="client_email" class="form-control @error('client_email') is-invalid @enderror"
               value="{{ old('client_email', $registro?->client_email) }}" placeholder="Ej. cliente@correo.com" maxlength="255">
        @error('client_email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="reservation_date" class="form-label fw-semibold">Fecha y hora <span class="text-danger">*</span></label>
        <input type="datetime-local" name="reservation_date" id="reservation_date"
               class="form-control @error('reservation_date') is-invalid @enderror"
               value="{{ old('reservation_date', isset($registro) && $registro->reservation_date ? $registro->reservation_date->format('Y-m-d\TH:i') : '') }}" required>
        @error('reservation_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="duration_minutes" class="form-label fw-semibold">Duración <span class="text-danger">*</span></label>
        <select name="duration_minutes" id="duration_minutes"
                class="form-select @error('duration_minutes') is-invalid @enderror">
            @foreach (range(30, 480, 15) as $min)
                <option value="{{ $min }}" @selected((int) old('duration_minutes', $registro?->duration_minutes ?? config('restauranager.reservation_duration_minutes', 120)) === $min)>
                    @if (intdiv($min, 60) > 0 && $min % 60 > 0)
                        {{ intdiv($min, 60) }} h {{ $min % 60 }} min
                    @elseif (intdiv($min, 60) > 0)
                        {{ intdiv($min, 60) }} h
                    @else
                        {{ $min }} min
                    @endif
                </option>
            @endforeach
        </select>
        @error('duration_minutes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text text-muted" id="previewFinReserva">
            @if ($registro?->reservation_end)
                Fin estimado: {{ time_es($registro->reservation_end) }}
            @endif
        </div>
    </div>

    <div class="col-md-2">
        <label for="people_count" class="form-label fw-semibold">Personas <span class="text-danger">*</span></label>
        <input type="number" name="people_count" id="people_count" min="1" step="1"
               class="form-control @error('people_count') is-invalid @enderror"
               value="{{ old('people_count', $registro?->people_count ?? 1) }}" required>
        @error('people_count')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="restaurant_table_id" class="form-label fw-semibold">Mesa</label>
        <select name="restaurant_table_id" id="restaurant_table_id" class="form-select @error('restaurant_table_id') is-invalid @enderror">
            <option value="">Sin mesa asignada</option>
            @foreach ($mesas as $mesa)
                <option value="{{ $mesa->id }}" @selected((string) old('restaurant_table_id', $registro?->restaurant_table_id) === (string) $mesa->id)>
                    {{ $mesa->table_number }} · {{ $mesa->capacity }} personas{{ $mesa->is_active ? '' : ' (inactiva)' }}
                </option>
            @endforeach
        </select>
        <div class="form-text">La capacidad de la mesa no puede ser superada por el número de personas.</div>
        @error('restaurant_table_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="status" class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
            <option value="">Selecciona...</option>
            @foreach ($estados as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('status', $registro?->status?->value ?? 'pending') === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label fw-semibold">Notas</label>
        <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror"
                  placeholder="Preferencias, celebración, alergias, acceso...">{{ old('notes', $registro?->notes) }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

@push('scripts')
<script>
(function () {
    const inputFecha    = document.getElementById('reservation_date');
    const selectDuracion = document.getElementById('duration_minutes');
    const previewDiv    = document.getElementById('previewFinReserva');

    if (!inputFecha || !selectDuracion || !previewDiv) return;

    function actualizarPreview() {
        const fechaVal = inputFecha.value;
        const duracion = parseInt(selectDuracion.value, 10);

        if (!fechaVal || isNaN(duracion)) {
            previewDiv.textContent = '';
            return;
        }

        const inicio = new Date(fechaVal);
        const fin    = new Date(inicio.getTime() + duracion * 60 * 1000);

        // Formato hh:mm am/pm legible (Colombia / 12h)
        const opciones = { hour: '2-digit', minute: '2-digit', hour12: true };
        previewDiv.textContent = 'Fin estimado: ' + fin.toLocaleTimeString('es-CO', opciones);
    }

    inputFecha.addEventListener('change', actualizarPreview);
    selectDuracion.addEventListener('change', actualizarPreview);

    // Ejecutar al cargar si ya hay valores (edición)
    actualizarPreview();
})();
</script>
@endpush
