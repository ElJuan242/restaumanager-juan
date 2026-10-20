@extends('layouts.app')

@section('title', 'Nuevo plato o bebida')
@section('breadcrumb', 'Menú / Platos y bebidas / Nuevo')

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header">
            <i class="bi bi-plus-lg me-2"></i>Registrar plato o bebida en el menú
        </div>

        <form method="POST" action="{{ route('menu-items.store') }}">
            @csrf

            <div class="card-body">
                @include('menus.items._form')
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('menu-items.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Guardar plato o bebida
                </button>
            </div>
        </form>
    </div>
@endsection
