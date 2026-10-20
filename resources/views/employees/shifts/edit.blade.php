@extends('layouts.app')

@section('title', 'Editar turno')
@section('breadcrumb', 'Turnos / Editar turno del ' . date_es($turno->date))

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header">
            <i class="bi bi-pencil me-2"></i>Editar turno de <strong>{{ $turno->employee?->name ?? '—' }}</strong>
        </div>

        <form method="POST" action="{{ route('shifts.update', $turno) }}">
            @csrf
            @method('PUT')

            <div class="card-body">
                @include('employees.shifts._form', ['turno' => $turno, 'empleados' => $empleados])
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Actualizar turno
                </button>
            </div>
        </form>
    </div>
@endsection
