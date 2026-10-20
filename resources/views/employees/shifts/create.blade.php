@extends('layouts.app')

@section('title', 'Nuevo turno')
@section('breadcrumb', 'Turnos / Nuevo turno')

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header">
            <i class="bi bi-plus-lg me-2"></i>Registrar turno
            @if (request('employee_id'))
                <span class="text-muted fw-normal">· empleado preseleccionado</span>
            @endif
        </div>

        <form method="POST" action="{{ route('shifts.store') }}">
            @csrf

            <div class="card-body">
                @include('employees.shifts._form', ['empleados' => $empleados])
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Guardar turno
                </button>
            </div>
        </form>
    </div>
@endsection
