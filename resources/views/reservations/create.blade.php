@extends('layouts.app')

@section('title', 'Nueva reserva')
@section('breadcrumb', 'Reservas / Nueva reserva')

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header">
            <i class="bi bi-plus-lg me-2"></i>Registrar reserva
        </div>

        <form method="POST" action="{{ route('reservations.store') }}">
            @csrf

            <div class="card-body">
                @include('reservations._form')
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Guardar reserva
                </button>
            </div>
        </form>
    </div>
@endsection
