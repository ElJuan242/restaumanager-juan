@extends('layouts.app')

@section('title', 'Editar cliente')
@section('breadcrumb', 'Clientes / Editar ' . $cliente->name)

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header">
            <i class="bi bi-pencil me-2"></i>Editar: {{ $cliente->name }}
        </div>

        <form method="POST" action="{{ route('customers.update', $cliente) }}">
            @csrf
            @method('PUT')

            <div class="card-body">
                @include('customers._form', ['cliente' => $cliente])
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Actualizar cliente
                </button>
            </div>
        </form>
    </div>
@endsection
