@extends('layouts.app')

@section('title', 'Nuevo empleado')
@section('breadcrumb', 'Empleados / Nuevo empleado')

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header">
            <i class="bi bi-plus-lg me-2"></i>Registrar empleado
        </div>

        <form method="POST" action="{{ route('employees.store') }}">
            @csrf

            <div class="card-body">
                @include('employees._form', ['roles' => $roles])
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Guardar empleado
                </button>
            </div>
        </form>
    </div>
@endsection
