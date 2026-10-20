@extends('layouts.app')

@section('title', 'Nueva categoría')
@section('breadcrumb', 'Menú / Categorías / Nueva categoría')

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header">
            <i class="bi bi-plus-lg me-2"></i>Registrar categoría del menú
        </div>

        <form method="POST" action="{{ route('menu-categories.store') }}">
            @csrf

            <div class="card-body">
                @include('menus.categories._form')
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('menu-categories.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Guardar categoría
                </button>
            </div>
        </form>
    </div>
@endsection
