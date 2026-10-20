@extends('layouts.app')

@section('title', 'Nuevo proveedor')
@section('breadcrumb', 'Proveedores / Nuevo proveedor')

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header">
            <i class="bi bi-plus-lg me-2"></i>Registrar proveedor
        </div>

        <form method="POST" action="{{ route('suppliers.store') }}">
            @csrf

            <div class="card-body">
                @include('suppliers._form')
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Guardar proveedor
                </button>
            </div>
        </form>
    </div>
@endsection
