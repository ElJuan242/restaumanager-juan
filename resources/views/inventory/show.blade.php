@extends('layouts.app')

@section('title', $registro->name)
@section('breadcrumb', 'Inventario / Detalle del producto')

@section('content')
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card rm-card">
                <div class="rm-card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-box-seam me-2"></i>{{ $registro->name }}</span>
                    <div class="d-flex gap-2">
                        <a href="{{ route('inventory.edit', $registro) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil me-1"></i>Editar
                        </a>
                        <form method="POST" action="{{ route('inventory.destroy', $registro) }}"
                              onsubmit="return confirm('¿Seguro que deseas eliminar este producto?');">
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
                        <dd class="col-sm-8">{!! badge($registro->categoryEnum()) !!}</dd>

                        <dt class="col-sm-4 text-muted">Descripción</dt>
                        <dd class="col-sm-8">{{ $registro->description ?? '—' }}</dd>

                        <dt class="col-sm-4 text-muted">Cantidad en stock</dt>
                        <dd class="col-sm-8 fw-bold {{ $registro->isLowStock() ? 'text-danger' : 'text-success' }}">
                            {{ rtrim(rtrim(number_format($registro->quantity, 2, ',', '.'), '0'), ',') }} {{ $registro->unit }}
                            @if ($registro->isLowStock())
                                <span class="badge bg-danger ms-2">Stock bajo</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted">Stock mínimo</dt>
                        <dd class="col-sm-8">{{ rtrim(rtrim(number_format($registro->min_stock, 2, ',', '.'), '0'), ',') }} {{ $registro->unit }}</dd>

                        <dt class="col-sm-4 text-muted">Precio unitario</dt>
                        <dd class="col-sm-8">{{ money_exact($registro->price) }}</dd>

                        <dt class="col-sm-4 text-muted">Valor del stock</dt>
                        <dd class="col-sm-8 fw-semibold">{{ money($registro->quantity * $registro->price) }}</dd>

                        <dt class="col-sm-4 text-muted">Estado</dt>
                        <dd class="col-sm-8">
                            @if ($registro->active)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted">Registrado</dt>
                        <dd class="col-sm-8">{{ date_es($registro->created_at) }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card rm-card">
                <div class="rm-card-header"><i class="bi bi-arrow-left-right me-2"></i>Ajustar cantidad</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('inventory.adjust-quantity', $registro) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="return_to" value="show">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Acción</label>
                            <select name="action" class="form-select">
                                <option value="sum">Sumar entrada</option>
                                <option value="subtract">Restar salida</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Motivo <span class="text-danger">*</span></label>
                            <input type="text" name="reason" class="form-control @error('reason') is-invalid @enderror"
                                   value="{{ old('reason') }}" maxlength="255" placeholder="Ej. Conteo físico, merma, corrección..." required>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Cantidad</label>
                            <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror"
                                   min="0.01" step="0.01" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-dark w-100">
                            <i class="bi bi-check2 me-1"></i>Aplicar ajuste
                        </button>
                    </form>

                    <hr>

                    <div class="small text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        El motivo es obligatorio. Si el ajuste deja el stock en negativo, se rechaza y no se modifica la cantidad.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
