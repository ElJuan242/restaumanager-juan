@extends('layouts.app')

@section('title', 'Editar orden de compra')
@section('breadcrumb', 'Órdenes de compra / Editar ' . $registro->po_number)

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header">
            <i class="bi bi-pencil me-2"></i>Editar: {{ $registro->po_number }}
        </div>

        <form method="POST" action="{{ route('purchases.update', $registro) }}">
            @csrf
            @method('PUT')

            <div class="card-body">
                @include('purchases._form', ['registro' => $registro])
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('purchases.show', $registro) }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Actualizar orden
                </button>
            </div>
        </form>
    </div>
@endsection
