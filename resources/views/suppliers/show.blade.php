@extends('layouts.app')

@section('title', $proveedor->name)
@section('breadcrumb', 'Proveedores / Detalle del proveedor')

@section('content')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card rm-card">
                <div class="rm-card-header d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-truck me-2"></i>{{ $proveedor->name }}
                        @if (! $proveedor->is_active)
                            <span class="badge bg-secondary ms-1">Inactivo</span>
                        @endif
                    </span>
                    <div class="d-flex gap-2">
                        <a href="{{ route('suppliers.edit', $proveedor) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil me-1"></i>Editar
                        </a>
                        <form method="POST" action="{{ route('suppliers.destroy', $proveedor) }}"
                              onsubmit="return confirm('¿Seguro que deseas eliminar este proveedor?');">
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
                        <dt class="col-sm-5 text-muted">Persona de contacto</dt>
                        <dd class="col-sm-7">{{ $proveedor->contact_name ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted">Teléfono</dt>
                        <dd class="col-sm-7">{{ $proveedor->phone ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted">Correo</dt>
                        <dd class="col-sm-7">{{ $proveedor->email ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted">Dirección</dt>
                        <dd class="col-sm-7">{{ $proveedor->address ?? '—' }}</dd>

                        <dt class="col-sm-5 text-muted">Categoría de suministro</dt>
                        <dd class="col-sm-7">
                            @if ($proveedor->category)
                                <span class="badge bg-light text-dark border">{{ $proveedor->category }}</span>
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-5 text-muted">Estado</dt>
                        <dd class="col-sm-7">
                            @if ($proveedor->is_active)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5 text-muted">Registrado</dt>
                        <dd class="col-sm-7">{{ date_es($proveedor->created_at) }}</dd>

                        <dt class="col-sm-5 text-muted">Notas</dt>
                        <dd class="col-sm-7">{{ $proveedor->notes ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <div class="card rm-card h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width:52px;height:52px;">
                                <i class="bi bi-cart-check fs-4"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Órdenes de compra</div>
                                <div class="fs-4 fw-bold">{{ $totalOrdenes }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card rm-card h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center bg-success-subtle text-success" style="width:52px;height:52px;">
                                <i class="bi bi-cash-stack fs-4"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Monto total comprado</div>
                                <div class="fs-4 fw-bold">{{ money($montoTotal) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card rm-card">
                <div class="rm-card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clock-history me-2"></i>Últimas órdenes de compra</span>
                    <a href="{{ route('purchases.index', ['supplier_id' => $proveedor->id]) }}" class="btn btn-sm btn-outline-secondary">Ver todas</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nº de orden</th>
                                <th>Fecha</th>
                                <th>Fecha esperada</th>
                                <th class="text-end">Total</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ordenes as $orden)
                                <tr>
                                    <td>
                                        <a href="{{ route('purchases.show', $orden) }}" class="fw-semibold text-decoration-none">
                                            {{ $orden->po_number }}
                                        </a>
                                    </td>
                                    <td>{{ date_es($orden->order_date) }}</td>
                                    <td>{{ date_es($orden->expected_date) }}</td>
                                    <td class="text-end">{{ money($orden->total) }}</td>
                                    <td>{!! badge($orden->status) !!}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="rm-empty">
                                            <i class="bi bi-inbox"></i>
                                            Este proveedor aún no tiene órdenes de compra registradas.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
