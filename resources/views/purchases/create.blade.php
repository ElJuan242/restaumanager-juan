@extends('layouts.app')

@section('title', 'Nueva orden de compra')
@section('breadcrumb', 'Órdenes de compra / Nueva orden')

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header">
            <i class="bi bi-plus-lg me-2"></i>Registrar orden de compra
        </div>

        <form method="POST" action="{{ route('purchases.store') }}">
            @csrf

            <div class="card-body">
                @include('purchases._form')
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Guardar orden
                </button>
            </div>
        </form>
    </div>
@endsection
