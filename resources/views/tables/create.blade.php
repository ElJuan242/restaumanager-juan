@extends('layouts.app')

@section('title', 'Nueva mesa')
@section('breadcrumb', 'Mesas / Nueva mesa')

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header">
            <i class="bi bi-plus-lg me-2"></i>Registrar mesa
        </div>

        <form method="POST" action="{{ route('tables.store') }}">
            @csrf

            <div class="card-body">
                @include('tables._form')
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('tables.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Guardar mesa
                </button>
            </div>
        </form>
    </div>
@endsection
