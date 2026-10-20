<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseOrderStatus;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    /**
     * Listado con búsqueda por número, filtro por estado y por proveedor.
     */
    public function index(Request $request)
    {
        $filtros = $request->only(['q', 'status', 'supplier_id']);

        $registros = PurchaseOrder::query()
            ->with(['supplier'])
            ->withCount('items')
            ->search($filtros['q'] ?? null)
            ->status($filtros['status'] ?? null)
            ->supplier(($filtros['supplier_id'] ?? null) ? (int) $filtros['supplier_id'] : null)
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('purchases.index', [
            'registros' => $registros,
            'estados' => PurchaseOrderStatus::options(),
            'proveedores' => Supplier::orderBy('name')->get(),
            'filtros' => $filtros,
        ]);
    }

    public function create()
    {
        return view('purchases.create', [
            'proveedores' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'insumos' => InventoryItem::orderBy('name')->get(),
            'estados' => PurchaseOrderStatus::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->validar($request);

        $orden = DB::transaction(function () use ($request) {
            $orden = PurchaseOrder::create([
                'supplier_id' => $request->supplier_id,
                'order_date' => $request->order_date,
                'expected_date' => $request->expected_date,
                'status' => $request->input('status') ?: PurchaseOrderStatus::Pending->value,
                'notes' => $request->notes,
            ]);

            $this->guardarItems($orden, $request->input('items', []));

            $orden->recalculateTotals();

            return $orden;
        });

        return redirect()
            ->route('purchases.show', $orden)
            ->with('success', 'Orden de compra '.$orden->po_number.' registrada correctamente.');
    }

    public function show(PurchaseOrder $purchase)
    {
        $purchase->load(['supplier', 'items.inventory']);

        return view('purchases.show', [
            'orden' => $purchase,
        ]);
    }

    public function edit(PurchaseOrder $purchase)
    {
        if ($purchase->isLocked()) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', 'No se puede editar una orden de compra recibida o cancelada.');
        }

        $purchase->load('items');

        return view('purchases.edit', [
            'registro' => $purchase,
            'proveedores' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'insumos' => InventoryItem::orderBy('name')->get(),
            'estados' => PurchaseOrderStatus::options(),
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        if ($purchase->isLocked()) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', 'No se puede editar una orden de compra recibida o cancelada.');
        }

        $this->validar($request);

        DB::transaction(function () use ($request, $purchase) {
            $nuevoEstado = $request->input('status') ?: PurchaseOrderStatus::Pending->value;

            $purchase->update([
                'supplier_id' => $request->supplier_id,
                'order_date' => $request->order_date,
                'expected_date' => $request->expected_date,
                'status' => $nuevoEstado,
                'notes' => $request->notes,
                'received_date' => $nuevoEstado === PurchaseOrderStatus::Received->value ? today() : $purchase->received_date,
            ]);

            // Se reemplazan los items anteriores por los recibidos desde el formulario.
            $purchase->items()->delete();
            $this->guardarItems($purchase, $request->input('items', []));

            $purchase->recalculateTotals();
        });

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Orden de compra '.$purchase->po_number.' actualizada correctamente.');
    }

    /**
     * Registra la recepción de mercancía e incrementa el stock del inventario.
     */
    public function receive(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        if ($purchase->isLocked()) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', 'No se puede recibir una orden de compra recibida o cancelada.');
        }

        $request->validate([
            'received' => ['nullable', 'array'],
            'received.*' => ['nullable', 'numeric', 'min:0'],
            'received_date' => ['nullable', 'date'],
        ], [], [
            'received' => 'cantidades recibidas',
            'received.*' => 'cantidad recibida',
            'received_date' => 'fecha de recepción',
        ]);

        $recibidas = $request->input('received', []);

        $itemsRecibidos = 0;

        DB::transaction(function () use ($purchase, $recibidas, &$itemsRecibidos) {
            $purchase->load('items.inventory');

            foreach ($purchase->items as $item) {
                $cantidad = (float) ($recibidas[$item->id] ?? 0);

                if ($cantidad <= 0) {
                    continue;
                }

                // Nunca se recibe más que el pendiente del item.
                $cantidad = min($cantidad, $item->pendingQty());

                if ($cantidad <= 0) {
                    continue;
                }

                if ($item->inventory) {
                    $item->inventory->adjustQuantity($cantidad);
                }

                $item->update(['received_qty' => $item->received_qty + $cantidad]);

                $itemsRecibidos++;
            }

            $purchase->refresh()->load('items');

            $todosCompletos = $purchase->items->isNotEmpty()
                && $purchase->items->every(fn (PurchaseOrderItem $item) => $item->isFullyReceived());

            $algunoRecibido = $purchase->items->contains(fn (PurchaseOrderItem $item) => $item->received_qty > 0);

            if ($todosCompletos) {
                $purchase->forceFill([
                    'status' => PurchaseOrderStatus::Received->value,
                    'received_date' => today(),
                ])->save();
            } elseif ($algunoRecibido) {
                $purchase->forceFill([
                    'status' => PurchaseOrderStatus::Partial->value,
                ])->save();
            }
        });

        $mensaje = $itemsRecibidos > 0
            ? sprintf('Recepción registrada: %d item(s) actualizados. Estado actual: %s.', $itemsRecibidos, $purchase->status->label())
            : 'No se registró ninguna cantidad recibida; el estado de la orden no cambió.';

        return redirect()->route('purchases.show', $purchase)->with('success', $mensaje);
    }

    public function destroy(PurchaseOrder $purchase): RedirectResponse
    {
        if ($purchase->isLocked()) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', 'No se puede eliminar una orden de compra recibida o cancelada.');
        }

        $numero = $purchase->po_number;

        $purchase->delete();

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Orden de compra '.$numero.' eliminada correctamente.');
    }

    /**
     * Crea los items de la orden copiando nombre y unidad del insumo del inventario.
     */
    private function guardarItems(PurchaseOrder $orden, array $items): void
    {
        foreach ($items as $fila) {
            $inventario = InventoryItem::find($fila['inventory_id'] ?? null);

            if (! $inventario) {
                continue;
            }

            $cantidad = (float) ($fila['quantity'] ?? 0);
            $precio = (float) ($fila['unit_price'] ?? 0);

            $orden->items()->create([
                'inventory_id' => $inventario->id,
                'item_name' => $inventario->name,
                'quantity' => $cantidad,
                'unit' => $inventario->unit,
                'unit_price' => $precio,
                'subtotal' => round($cantidad * $precio, 2),
            ]);
        }
    }

    /**
     * Reglas compartidas de validación del módulo.
     */
    private function validar(Request $request): array
    {
        return $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'status' => ['nullable', 'in:'.implode(',', array_keys(PurchaseOrderStatus::options()))],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_id' => ['required', 'exists:inventory,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ], [], [
            'supplier_id' => 'proveedor',
            'order_date' => 'fecha de orden',
            'expected_date' => 'fecha esperada',
            'status' => 'estado',
            'notes' => 'notas',
            'items' => 'items',
            'items.*.inventory_id' => 'insumo',
            'items.*.quantity' => 'cantidad',
            'items.*.unit_price' => 'precio unitario',
        ]);
    }
}
