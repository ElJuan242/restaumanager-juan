@extends('layouts.app')

@section('title', 'Editar reserva')
@section('breadcrumb', 'Reservas / Editar reserva de ' . $registro->client_name)

@section('content')
    <div class="card rm-card">
        <div class="rm-card-header">
            <i class="bi bi-pencil me-2"></i>Editar reserva de: {{ $registro->client_name }}
        </div>

        <form method="POST" action="{{ route('reservations.update', $registro) }}">
            @csrf
            @method('PUT')

            <div class="card-body">
                @include('reservations._form', ['registro' => $registro])
            </div>

            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Actualizar reserva
                </button>
            </div>
        </form>
    </div>
@endsection
