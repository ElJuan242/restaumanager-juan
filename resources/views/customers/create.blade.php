@extends('layouts.app')

@section('title', 'Nuevo cliente')
@section('breadcrumb', 'Clientes / Nuevo cliente')

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header">
            <i class="bi bi-plus-lg me-2"></i>Registrar cliente
        </div>

        <form method="POST" action="{{ route('customers.store') }}">
            @csrf

            <div class="card-body">
                @include('customers._form')
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Guardar cliente
                </button>
            </div>
        </form>
    </div>
@endsection
