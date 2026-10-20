@extends('layouts.app')

@section('title', 'Editar orden')
@section('breadcrumb', 'Órdenes / Editar ' . $registro->order_number)

@section('content')
    <form method="POST" action="{{ route('orders.update', $registro) }}">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card rm-card mb-3">
                    <div class="rm-card-header"><i class="bi bi-pencil me-2"></i>Editar {{ $registro->order_number }}</div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="source" class="form-label fw-semibold">Origen <span class="text-danger">*</span></label>
                                <select name="source" id="source" class="form-select @error('source') is-invalid @enderror" required>
                                    @foreach ($origenes as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected(old('source', $registro->source->value) === $valor)>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                                @error('source')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="restaurant_table_id" class="form-label fw-semibold">Mesa</label>
                                <select name="restaurant_table_id" id="restaurant_table_id" class="form-select @error('restaurant_table_id') is-invalid @enderror">
                                    <option value="">Sin mesa</option>
                                    @foreach ($mesas as $mesa)
                                        <option value="{{ $mesa->id }}" @selected((string) old('restaurant_table_id', $registro->restaurant_table_id) === (string) $mesa->id)>
                                            {{ $mesa->table_number }} · {{ $mesa->locationEnum()->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('restaurant_table_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="status" class="form-label fw-semibold">Estado</label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                    @foreach ($estados as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected(old('status', $registro->status->value) === $valor)>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="customer_id" class="form-label fw-semibold">Cliente</label>
                                <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror">
                                    <option value="">Sin cliente</option>
                                    @foreach ($clientes as $cliente)
                                        <option value="{{ $cliente->id }}" @selected((string) old('customer_id', $registro->customer_id) === (string) $cliente->id)>{{ $cliente->name }}</option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="employee_id" class="form-label fw-semibold">Empleado</label>
                                <select name="employee_id" id="employee_id" class="form-select @error('employee_id') is-invalid @enderror">
                                    <option value="">Sin empleado</option>
                                    @foreach ($empleados as $empleado)
                                        <option value="{{ $empleado->id }}" @selected((string) old('employee_id', $registro->employee_id) === (string) $empleado->id)>
                                            {{ $empleado->name }} · {{ $empleado->role->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('employee_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="notes" class="form-label fw-semibold">Notas</label>
                                <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror"
                                          placeholder="Indicaciones especiales para la orden...">{{ old('notes', $registro->notes) }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-white d-flex justify-content-end gap-2">
                        <a href="{{ route('orders.show', $registro) }}" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Actualizar orden
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card rm-card mb-3">
                    <div class="rm-card-header"><i class="bi bi-calculator me-2"></i>Totales</div>
                    <div class="card-body">
                        <dl class="row mb-2">
                            <dt class="col-7 text-muted">Subtotal</dt>
                            <dd class="col-5 text-end">{{ money($registro->subtotal) }}</dd>

                            <dt class="col-7 text-muted">IVA ({{ pct(19) }})</dt>
                            <dd class="col-5 text-end">{{ money($registro->tax) }}</dd>

                            <dt class="col-7 fw-bold fs-5">Total</dt>
                            <dd class="col-5 text-end fw-bold fs-5">{{ money($registro->total) }}</dd>
                        </dl>

                        <div class="small text-muted">
                            <i class="bi bi-info-circle me-1"></i>Los items se añaden desde la ficha de la orden.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="card rm-card">
        <div class="rm-card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-ul me-2"></i>Items de la orden</span>
            <a href="{{ route('orders.show', $registro) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Añadir items
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Plato</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-end">Precio unit.</th>
                        <th class="text-end">Subtotal</th>
                        <th>Estado</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registro->items as $item)
                        <tr>
                            <td class="fw-semibold">{{ $item->item_name }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">{{ money($item->unit_price) }}</td>
                            <td class="text-end fw-semibold">{{ money($item->subtotal) }}</td>
                            <td>{!! badge($item->status) !!}</td>
                            <td class="small text-muted">{{ $item->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="rm-empty">
                                    <i class="bi bi-basket"></i>
                                    Esta orden todavía no tiene items.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
