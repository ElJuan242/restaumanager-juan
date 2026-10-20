@extends('layouts.app')

@section('title', 'Editar categoría')
@section('breadcrumb', 'Menú / Categorías / Editar ' . $registro->name)

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header">
            <i class="bi bi-pencil me-2"></i>Editar: {{ $registro->name }}
        </div>

        <form method="POST" action="{{ route('menu-categories.update', $registro) }}">
            @csrf
            @method('PUT')

            <div class="card-body">
                @include('menus.categories._form', ['registro' => $registro])
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('menu-categories.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Actualizar categoría
                </button>
            </div>
        </form>
    </div>
@endsection
