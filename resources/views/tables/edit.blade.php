@extends('layouts.app')

@section('title', 'Editar mesa')
@section('breadcrumb', 'Mesas / Editar ' . $registro->table_number)

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header">
            <i class="bi bi-pencil me-2"></i>Editar mesa: {{ $registro->table_number }}
        </div>

        <form method="POST" action="{{ route('tables.update', $registro) }}">
            @csrf
            @method('PUT')

            <div class="card-body">
                @include('tables._form', ['registro' => $registro])
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('tables.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Actualizar mesa
                </button>
            </div>
        </form>
    </div>
@endsection
