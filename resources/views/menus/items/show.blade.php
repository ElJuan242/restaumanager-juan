@extends('layouts.app')

@section('title', $registro->name)
@section('breadcrumb', 'Menú / Platos y bebidas / Detalle')

@section('content')
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card rm-card">
                <div class="rm-card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-egg-fried me-2"></i>{{ $registro->name }}</span>
                    <div class="d-flex gap-2">
                        <a href="{{ route('menu-items.edit', $registro) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil me-1"></i>Editar
                        </a>
                        <form method="POST" action="{{ route('menu-items.destroy', $registro) }}"
                              onsubmit="return confirm('¿Seguro que deseas eliminar este plato o bebida del menú?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash me-1"></i>Eliminar
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Categoría</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-primary bg-opacity-10 text-primary">{{ $registro->category?->name ?? '—' }}</span>
                        </dd>

                        <dt class="col-sm-4 text-muted">Descripción</dt>
                        <dd class="col-sm-8">{{ $registro->description ?? '—' }}</dd>

                        <dt class="col-sm-4 text-muted">Precio</dt>
                        <dd class="col-sm-8 fw-bold">{{ money_exact($registro->price) }}</dd>

                        <dt class="col-sm-4 text-muted">Ingredientes</dt>
                        <dd class="col-sm-8">
                            @if (count($registro->ingredientsList()) > 0)
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ($registro->ingredientsList() as $ingrediente)
                                        <span class="badge bg-light text-dark border">{{ $ingrediente }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted fst-italic">Sin ingredientes registrados</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted">Disponibilidad</dt>
                        <dd class="col-sm-8">
                            @if ($registro->is_available)
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Disponible</span>
                            @else
                                <span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>No disponible</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted">Destacado</dt>
                        <dd class="col-sm-8">
                            @if ($registro->is_featured)
                                <span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1"></i>Destacado</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted">Orden</dt>
                        <dd class="col-sm-8">{{ $registro->sort_order }}</dd>

                        <dt class="col-sm-4 text-muted">Imagen</dt>
                        <dd class="col-sm-8 text-truncate" style="max-width: 100%;">{{ $registro->image ?? '—' }}</dd>

                        <dt class="col-sm-4 text-muted">Registrado</dt>
                        <dd class="col-sm-8">{{ date_es($registro->created_at) }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card rm-card">
                <div class="rm-card-header"><i class="bi bi-toggle-on me-2"></i>Disponibilidad</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('menu-items.toggle-available', $registro) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="return_to" value="show">

                        <button type="submit" class="btn {{ $registro->is_available ? 'btn-outline-secondary' : 'btn-success' }} w-100">
                            @if ($registro->is_available)
                                <i class="bi bi-eye-slash me-1"></i>Marcar como no disponible
                            @else
                                <i class="bi bi-check2-circle me-1"></i>Marcar como disponible
                            @endif
                        </button>
                    </form>

                    <hr>

                    <div class="small text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Los elementos no disponibles no deberán agregarse a nuevas órdenes.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
