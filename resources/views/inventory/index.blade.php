@extends('layouts.app')

@section('title', 'Inventario')
@section('breadcrumb', 'Productos e insumos del restaurante')

@section('content')
    <div class="card rm-card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('inventory.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Buscar por nombre</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="{{ $filtros['q'] ?? '' }}" class="form-control" placeholder="Ej. Pollo, Arroz...">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Categoría</label>
                    <select name="category" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($categorias as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(($filtros['category'] ?? '') === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="low_stock" value="1" id="low_stock"
                               @checked(isset($filtros['low_stock']))>
                        <label class="form-check-label small fw-semibold" for="low_stock">
                            <i class="bi bi-exclamation-triangle text-warning me-1"></i>Solo stock bajo
                        </label>
                    </div>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark flex-fill">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card rm-card">
        <div class="rm-card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-box-seam me-2"></i>Productos <span class="badge bg-secondary">{{ $registros->total() }}</span></span>
            <a href="{{ route('inventory.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nuevo producto
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">Precio</th>
                        <th class="text-end">Mínimo</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registros as $registro)
                        <tr>
                            <td>
                                <a href="{{ route('inventory.show', $registro) }}" class="fw-semibold text-decoration-none">
                                    {{ $registro->name }}
                                </a>
                                @if ($registro->description)
                                    <div class="small text-muted text-truncate" style="max-width: 260px;">{{ $registro->description }}</div>
                                @endif
                            </td>
                            <td>{!! badge($registro->categoryEnum()) !!}</td>
                            <td class="text-end fw-semibold {{ $registro->isLowStock() ? 'text-danger' : '' }}">
                                {{ rtrim(rtrim(number_format($registro->quantity, 2, ',', '.'), '0'), ',') }} {{ $registro->unit }}
                            </td>
                            <td class="text-end">{{ money($registro->price) }}</td>
                            <td class="text-end text-muted">{{ rtrim(rtrim(number_format($registro->min_stock, 2, ',', '.'), '0'), ',') }}</td>
                            <td>
                                @if ($registro->isLowStock())
                                    <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Stock bajo</span>
                                @elseif (! $registro->active)
                                    <span class="badge bg-secondary">Inactivo</span>
                                @else
                                    <span class="badge bg-success">OK</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('inventory.show', $registro) }}" class="btn btn-outline-secondary" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-primary" title="Ajustar cantidad"
                                            data-bs-toggle="modal" data-bs-target="#modalAjuste"
                                            data-id="{{ $registro->id }}"
                                            data-name="{{ $registro->name }}"
                                            data-quantity="{{ $registro->quantity }}"
                                            data-unit="{{ $registro->unit }}"
                                            data-action="{{ route('inventory.adjust-quantity', $registro) }}">
                                        <i class="bi bi-arrow-left-right"></i>
                                    </button>
                                    <a href="{{ route('inventory.edit', $registro) }}" class="btn btn-outline-secondary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Eliminar"
                                            data-bs-toggle="modal" data-bs-target="#modalEliminar{{ $registro->id }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="rm-empty">
                                    <i class="bi bi-inbox"></i>
                                    No se encontraron productos con los filtros aplicados.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($registros->hasPages())
            <div class="card-footer bg-white d-flex justify-content-center">
                {{ $registros->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    {{-- Modal: ajuste de cantidad --}}
    <div class="modal fade" id="modalAjuste" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formAjuste" class="modal-content">
                @csrf
                @method('PATCH')
                <input type="hidden" name="return_to" value="index">

                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrow-left-right me-2"></i>Ajustar cantidad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p class="mb-2">
                        Producto: <strong id="ajusteNombre">—</strong><br>
                        Stock actual: <strong id="ajusteStock">—</strong>
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action" id="actionSum" value="sum" checked>
                                <label class="form-check-label" for="actionSum"><i class="bi bi-plus-circle text-success me-1"></i>Sumar</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action" id="actionSubtract" value="subtract">
                                <label class="form-check-label" for="actionSubtract"><i class="bi bi-dash-circle text-danger me-1"></i>Restar</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control @error('reason') is-invalid @enderror"
                               value="{{ old('reason') }}" maxlength="255" placeholder="Ej. Conteo físico, merma, corrección..." required>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">Cantidad</label>
                        <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror"
                               min="0.01" step="0.01" required>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Si el ajuste deja el stock en negativo, se rechaza y no se modifica la cantidad.</div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar ajuste</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modales: eliminación --}}
    @foreach ($registros as $registro)
        <div class="modal fade" id="modalEliminar{{ $registro->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('inventory.destroy', $registro) }}" class="modal-content">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Eliminar producto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        ¿Seguro que deseas eliminar <strong>{{ $registro->name }}</strong> del inventario?
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection

@push('scripts')
<script>
    const modalAjuste = document.getElementById('modalAjuste');

    modalAjuste.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        if (!trigger) return;

        document.getElementById('formAjuste').action = trigger.dataset.action;
        document.getElementById('ajusteNombre').textContent = trigger.dataset.name;
        document.getElementById('ajusteStock').textContent = trigger.dataset.quantity + ' ' + trigger.dataset.unit;
    });
</script>
@endpush
