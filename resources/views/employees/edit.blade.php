@extends('layouts.app')

@section('title', 'Editar empleado')
@section('breadcrumb', 'Empleados / Editar ' . $empleado->name)

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header">
            <i class="bi bi-pencil me-2"></i>Editar: {{ $empleado->name }}
        </div>

        <form method="POST" action="{{ route('employees.update', $empleado) }}">
            @csrf
            @method('PUT')

            <div class="card-body">
                @include('employees._form', ['empleado' => $empleado, 'roles' => $roles])
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Actualizar empleado
                </button>
            </div>
        </form>
    </div>
@endsection
