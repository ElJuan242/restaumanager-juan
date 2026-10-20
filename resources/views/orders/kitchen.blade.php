@extends('layouts.app')

@section('title', 'Cocina')
@section('breadcrumb', 'Vista de cocina · órdenes activas')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div class="text-muted small">
            <i class="bi bi-arrow-repeat me-1"></i>La vista se actualiza automáticamente cada 30 segundos.
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise me-1"></i>Actualizar ahora
            </button>
            <a href="{{ route('orders.index') }}" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-receipt-cutoff me-1"></i>Ir a órdenes
            </a>
        </div>
    </div>

    <div class="row g-3">
        @forelse ($registros as $orden)
            <div class="col-md-6 col-xl-4">
                <div class="card rm-card h-100">
                    <div class="rm-card-header d-flex justify-content-between align-items-center">
                        <span>
                            <i class="bi bi-receipt me-2"></i>{{ $orden->order_number }}
                        </span>
                        {!! badge($orden->status) !!}
                    </div>

                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                @if ($orden->table)
                                    <span class="fw-semibold"><i class="bi bi-grid-3x3-gap me-1"></i>Mesa {{ $orden->table->table_number }}</span>
                                @else
                                    <span class="fw-semibold text-muted"><i class="bi bi-bag me-1"></i>Sin mesa</span>
                                @endif
                                <div class="small">{!! badge($orden->source) !!}</div>
                            </div>
                            <div class="text-end small text-muted">
                                <i class="bi bi-clock me-1"></i>{{ time_es($orden->created_at) }}
                                <div>{{ date_es($orden->created_at) }}</div>
                            </div>
                        </div>

                        <ul class="list-group list-group-flush">
                            @foreach ($orden->items as $item)
                                <li class="list-group-item px-0 {{ $item->status === \App\Enums\OrderItemStatus::Preparing ? 'bg-warning-subtle' : '' }}">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <span class="fw-semibold">{{ $item->quantity }}× {{ $item->item_name }}</span>
                                            @if ($item->status === \App\Enums\OrderItemStatus::Preparing)
                                                <span class="badge bg-warning text-dark ms-1">En preparación</span>
                                            @endif
                                            @if ($item->notes)
                                                <div class="small text-muted"><i class="bi bi-chat-left-text me-1"></i>{{ $item->notes }}</div>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            {!! badge($item->status) !!}
                                        </div>
                                    </div>

                                    @php
                                        $siguiente = match ($item->status) {
                                            \App\Enums\OrderItemStatus::Pending => \App\Enums\OrderItemStatus::Preparing,
                                            \App\Enums\OrderItemStatus::Preparing => \App\Enums\OrderItemStatus::Ready,
                                            \App\Enums\OrderItemStatus::Ready => \App\Enums\OrderItemStatus::Served,
                                            default => null,
                                        };
                                    @endphp

                                    @if ($siguiente)
                                        <form method="POST" action="{{ route('orders.item-status', $item) }}" class="mt-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $siguiente->value }}">
                                            <button type="submit" class="btn btn-sm btn-dark w-100">
                                                <i class="bi bi-arrow-right-circle me-1"></i>Marcar como {{ $siguiente->label() }}
                                            </button>
                                        </form>
                                    @else
                                        <div class="small text-success mt-2">
                                            <i class="bi bi-check2-circle me-1"></i>Item finalizado
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                        <span class="small text-muted">{{ $orden->items->count() }} items</span>
                        <a href="{{ route('orders.show', $orden) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye me-1"></i>Ver orden
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card rm-card">
                    <div class="card-body">
                        <div class="rm-empty">
                            <i class="bi bi-check2-circle"></i>
                            No hay órdenes activas en cocina en este momento.
                        </div>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
@endsection

@push('scripts')
<meta http-equiv="refresh" content="30">
<script>
    // Auto-refresco opcional de la vista de cocina cada 30 segundos.
    setTimeout(function () {
        if (! document.hidden) {
            location.reload();
        }
    }, 30000);
</script>
@endpush
