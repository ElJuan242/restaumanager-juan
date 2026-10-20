@extends('layouts.app')

@section('title', 'Nuevo producto')
@section('breadcrumb', 'Inventario / Nuevo producto')

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header">
            <i class="bi bi-plus-lg me-2"></i>Registrar producto en el inventario
        </div>

        <form method="POST" action="{{ route('inventory.store') }}">
            @csrf

            <div class="card-body">
                @include('inventory._form')
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Guardar producto
                </button>
            </div>
        </form>
    </div>
@endsection
