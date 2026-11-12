<?php

namespace App\Http\Controllers;

use App\Enums\InventoryMovementType;
use App\Enums\PurchaseOrderStatus;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Services\AdminPinService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    public function __construct(private AdminPinService $adminPinService) {}

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
            'estados' => $this->estadosEditables(),
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
        $purchase->load(['supplier', 'items.inventory', 'closedBy']);

        return view('purchases.show', [
            'orden' => $purchase,
        ]);
    }

    public function edit(PurchaseOrder $purchase)
    {
        if ($purchase->isLocked()) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', 'No se puede editar una orden de compra en estado '.$purchase->status->label().'.');
        }

        $purchase->load('items');

        return view('purchases.edit', [
            'registro' => $purchase,
            'proveedores' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'insumos' => InventoryItem::orderBy('name')->get(),
            'estados' => $this->estadosEditables(),
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        if ($purchase->isLocked()) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', 'No se puede editar una orden de compra en estado '.$purchase->status->label().'.');
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
                ->with('error', 'No se puede recibir una orden de compra en estado '.$purchase->status->label().'.');
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

                    $item->inventory->recordMovement(
                        InventoryMovementType::PurchaseReceipt,
                        $cantidad,
                        'Recepción de compra '.$purchase->po_number,
                        $purchase,
                        auth()->id()
                    );
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

    /**
     * Cierra forzosamente una orden parcial (RN-07).
     *
     * El inventario no se modifica: solo se cierra la orden con lo recibido,
     * recalculando el total sobre received_qty x unit_price + IVA 19%.
     */
    public function close(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        if ($purchase->status !== PurchaseOrderStatus::Partial) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', 'Solo se pueden cerrar forzosamente las órdenes de compra en estado parcial.');
        }

        $request->merge([
            'closed_reason' => trim((string) $request->input('closed_reason')),
            'pin' => trim((string) $request->input('pin')),
        ]);

        $data = $request->validate([
            'closed_reason' => ['required', 'string', 'max:500'],
            'pin' => ['required', 'string', 'max:20'],
        ], [
            'closed_reason.required' => 'Debes indicar el motivo del cierre forzoso.',
            'closed_reason.max' => 'El motivo del cierre no puede superar los 500 caracteres.',
            'pin.required' => 'Debes ingresar el PIN de administrador para cerrar la orden.',
            'pin.max' => 'El PIN de administrador no es válido.',
        ], [
            'closed_reason' => 'motivo del cierre',
            'pin' => 'PIN de administrador',
        ]);

        try {
            DB::transaction(function () use ($purchase, $data) {
                $bloqueada = PurchaseOrder::query()
                    ->whereKey($purchase->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($bloqueada->status !== PurchaseOrderStatus::Partial) {
                    throw ValidationException::withMessages([
                        'closed_reason' => 'Solo se pueden cerrar forzosamente las órdenes de compra en estado parcial.',
                    ]);
                }

                $admin = $this->adminPinService->verify(
                    $data['pin'],
                    'purchase_close_'.$bloqueada->getKey()
                );

                if (! $admin) {
                    throw ValidationException::withMessages([
                        'pin' => 'PIN de administrador inválido.',
                    ]);
                }

                $bloqueada->forceFill([
                    'status' => PurchaseOrderStatus::Closed->value,
                    'closed_by' => $admin->getKey(),
                    'closed_at' => now(),
                    'closed_reason' => $data['closed_reason'],
                ])->save();

                $bloqueada->recalculateTotalsFromReceived();
            });
        } catch (ValidationException $e) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Throwable $e) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', 'No se pudo cerrar la orden de compra. Intenta de nuevo.')
                ->withInput();
        }

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Orden de compra '.$purchase->po_number.' cerrada forzosamente.');
    }

    public function destroy(PurchaseOrder $purchase): RedirectResponse
    {
        if ($purchase->isLocked()) {
            return redirect()
                ->route('purchases.show', $purchase)
                ->with('error', 'No se puede eliminar una orden de compra en estado '.$purchase->status->label().'.');
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
     * Estados seleccionables manualmente. El estado cerrada solo se aplica
     * mediante la acción de cierre forzoso con PIN de administrador.
     */
    private function estadosEditables(): array
    {
        $estados = PurchaseOrderStatus::options();
        unset($estados[PurchaseOrderStatus::Closed->value]);

        return $estados;
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
            'status' => ['nullable', 'in:'.implode(',', array_keys($this->estadosEditables()))],
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
