@php($turno = $turno ?? null)
@php($hora = fn ($valor) => ($valor !== null && $valor !== '') ? substr((string) $valor, 0, 5) : '')

<div class="row g-3">
    <div class="col-md-6">
        <label for="employee_id" class="form-label fw-semibold">Empleado <span class="text-danger">*</span></label>
        <select name="employee_id" id="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
            <option value="">Selecciona...</option>
            @foreach ($empleados as $empleado)
                <option value="{{ $empleado->id }}"
                        @selected((string) old('employee_id', $turno?->employee_id ?? request('employee_id')) === (string) $empleado->id)>
                    {{ $empleado->name }}{{ $empleado->position ? ' · '.$empleado->position : '' }}
                </option>
            @endforeach
        </select>
        @error('employee_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="date" class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
        <input type="date" name="date" id="date"
               class="form-control @error('date') is-invalid @enderror"
               value="{{ old('date', $turno?->date?->toDateString() ?? today()->toDateString()) }}" required>
        @error('date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="start_time" class="form-label fw-semibold">Hora de inicio <span class="text-danger">*</span></label>
        <input type="time" name="start_time" id="start_time"
               class="form-control @error('start_time') is-invalid @enderror"
               value="{{ old('start_time', $hora($turno?->start_time)) }}" required>
        @error('start_time')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="end_time" class="form-label fw-semibold">Hora de fin <span class="text-danger">*</span></label>
        <input type="time" name="end_time" id="end_time"
               class="form-control @error('end_time') is-invalid @enderror"
               value="{{ old('end_time', $hora($turno?->end_time)) }}" required>
        @error('end_time')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">Debe ser posterior a la hora de inicio (no se permiten turnos que crucen la medianoche).</div>
    </div>

    <div class="col-md-3">
        <label for="actual_start" class="form-label fw-semibold">Hora real de inicio</label>
        <input type="time" name="actual_start" id="actual_start"
               class="form-control @error('actual_start') is-invalid @enderror"
               value="{{ old('actual_start', $hora($turno?->actual_start)) }}">
        @error('actual_start')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="actual_end" class="form-label fw-semibold">Hora real de fin</label>
        <input type="time" name="actual_end" id="actual_end"
               class="form-control @error('actual_end') is-invalid @enderror"
               value="{{ old('actual_end', $hora($turno?->actual_end)) }}">
        @error('actual_end')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="notes" class="form-label fw-semibold">Notas</label>
        <textarea name="notes" id="notes" rows="2"
                  class="form-control @error('notes') is-invalid @enderror"
                  placeholder="Observaciones del turno, incidencias, cobertura...">{{ old('notes', $turno?->notes) }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 small text-muted">
        <i class="bi bi-info-circle me-1"></i>
        Las horas reales son opcionales: la duración real se calcula automáticamente a partir de ellas.
    </div>
</div>
