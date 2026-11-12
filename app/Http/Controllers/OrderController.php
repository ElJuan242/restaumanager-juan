<?php

namespace App\Http\Controllers;

use App\Enums\BillStatus;
use App\Enums\OrderItemStatus;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RestaurantTable;
use App\Services\AdminPinService;
use App\Services\RecipeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(
        private RecipeService $recipeService,
        private AdminPinService $adminPinService
    ) {}

    /**
     * Listado con búsqueda por número, filtro de estado, mesa y origen.
     */
    public function index(Request $request)
    {
        $filtros = $request->only(['q', 'status', 'table', 'source']);

        $registros = Order::query()
            ->search($filtros['q'] ?? null)
            ->status($filtros['status'] ?? null)
            ->table($filtros['table'] ?? null)
            ->source($filtros['source'] ?? null)
            ->with(['table', 'customer', 'employee'])
            ->withCount('items')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('orders.index', [
            'registros' => $registros,
            'estados' => OrderStatus::options(),
            'origenes' => OrderSource::options(),
            'mesas' => RestaurantTable::active()->orderBy('table_number')->get(),
            'filtros' => $filtros,
        ]);
    }

    public function create()
    {
        $estados = OrderStatus::options();
        unset($estados[OrderStatus::Cancelled->value]);

        return view('orders.create', [
            'platos' => MenuItem::where('is_available', true)->with('category')->orderBy('name')->get(),
            'mesas' => RestaurantTable::active()->orderBy('table_number')->get(),
            'clientes' => Customer::orderBy('name')->get(),
            'empleados' => Employee::active()->orderBy('name')->get(),
            'origenes' => OrderSource::options(),
            'estados' => $estados,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validarCabecera($request);
        $items = $this->validarItems($request);

        DB::beginTransaction();

        try {
            $orden = Order::create($data);

            $this->crearItems($orden, $items);

            $orden->recalculateTotals();

            if ($orden->source === OrderSource::DineIn && $orden->restaurant_table_id) {
                $mesa = RestaurantTable::find($orden->restaurant_table_id);

                if ($mesa) {
                    $mesa->setStatus(TableStatus::Occupied);
                }
            }

            DB::commit();
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()->route('orders.create')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->route('orders.create')
                ->with('error', 'No se pudo registrar la orden. Intenta de nuevo.')
                ->withInput();
        }

        return redirect()->route('orders.show', $orden)
            ->with('success', 'Orden '.$orden->order_number.' registrada correctamente.');
    }

    public function show(Order $order)
    {
        $order->load([
            'table',
            'customer',
            'employee',
            'items.menuItem',
            'bill.payments',
            'cancelledBy',
            'authorizedBy',
        ]);

        return view('orders.show', [
            'registro' => $order,
            'estadosItem' => OrderItemStatus::options(),
            'estados' => $this->estadosDisponibles($order),
            'platos' => MenuItem::where('is_available', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(Order $order)
    {
        $order->load('items.menuItem');

        return view('orders.edit', [
            'registro' => $order,
            'platos' => MenuItem::where('is_available', true)->with('category')->orderBy('name')->get(),
            'mesas' => RestaurantTable::active()->orderBy('table_number')->get(),
            'clientes' => Customer::orderBy('name')->get(),
            'empleados' => Employee::active()->orderBy('name')->get(),
            'origenes' => OrderSource::options(),
            'estados' => $this->estadosDisponibles($order),
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $this->validarCabecera($request, $order);

        DB::beginTransaction();

        try {
            $estadoAnterior = $order->status;

            $order->update($data);
            $order->recalculateTotals();
            $order->refresh();

            // Al pasar a un estado cerrable se libera la mesa asociada.
            if ($order->status->isClosable()) {
                $order->releaseTable();
            } elseif ($estadoAnterior->isClosable() === false
                && $order->source === OrderSource::DineIn
                && $order->restaurant_table_id) {
                $mesa = RestaurantTable::find($order->restaurant_table_id);

                if ($mesa && $mesa->status === TableStatus::Available) {
                    $mesa->setStatus(TableStatus::Occupied);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->route('orders.edit', $order)
                ->with('error', 'No se pudo actualizar la orden. Intenta de nuevo.')
                ->withInput();
        }

        return redirect()->route('orders.show', $order)
            ->with('success', 'Orden actualizada correctamente.');
    }

    /**
     * Cancela una orden aplicando RN-06.
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $order->loadMissing(['bill.payments', 'items.menuItem.recipeItems', 'table']);

        if ($order->status === OrderStatus::Served) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'No se puede cancelar una orden servida.');
        }

        if ($order->status === OrderStatus::Cancelled) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'La orden ya está cancelada.');
        }

        if ($order->bill && $order->bill->hasPayments()) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'No se puede cancelar la orden porque su factura tiene pagos registrados.');
        }

        $request->merge([
            'cancel_reason' => trim((string) $request->input('cancel_reason')),
            'pin' => trim((string) $request->input('pin')),
        ]);

        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:500'],
            'pin' => ['nullable', 'string', 'max:20'],
        ], [
            'cancel_reason.required' => 'Debes indicar el motivo de la cancelación.',
            'cancel_reason.max' => 'El motivo de cancelación no puede superar los 500 caracteres.',
            'pin.max' => 'El PIN de administrador no es válido.',
        ], [
            'cancel_reason' => 'motivo de cancelación',
            'pin' => 'PIN de administrador',
        ]);

        try {
            DB::transaction(function () use ($order, $data) {
                $bloqueada = Order::query()
                    ->whereKey($order->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $bloqueada->loadMissing(['bill.payments', 'items.menuItem.recipeItems', 'table']);

                if ($bloqueada->status === OrderStatus::Served) {
                    throw ValidationException::withMessages([
                        'status' => 'No se puede cancelar una orden servida.',
                    ]);
                }

                if ($bloqueada->status === OrderStatus::Cancelled) {
                    throw ValidationException::withMessages([
                        'status' => 'La orden ya está cancelada.',
                    ]);
                }

                if ($bloqueada->bill && $bloqueada->bill->hasPayments()) {
                    throw ValidationException::withMessages([
                        'cancel_reason' => 'No se puede cancelar la orden porque su factura tiene pagos registrados.',
                    ]);
                }

                $requierePin = in_array($bloqueada->status, [
                    OrderStatus::Preparing,
                    OrderStatus::Ready,
                ], true);

                $admin = null;

                if ($requierePin) {
                    $pin = (string) ($data['pin'] ?? '');

                    if ($pin === '') {
                        throw ValidationException::withMessages([
                            'pin' => 'Debes ingresar el PIN de administrador para cancelar una orden en preparación o lista.',
                        ]);
                    }

                    $admin = $this->adminPinService->verify(
                        $pin,
                        'order_cancel_'.$bloqueada->getKey()
                    );

                    if (! $admin) {
                        throw ValidationException::withMessages([
                            'pin' => 'PIN de administrador inválido.',
                        ]);
                    }
                }

                if ($requierePin) {
                    foreach ($bloqueada->items as $item) {
                        if (in_array($item->status, [
                            OrderItemStatus::Preparing,
                            OrderItemStatus::Ready,
                        ], true)) {
                            $this->recipeService->recordWasteForOrderItem(
                                $item,
                                $data['cancel_reason'],
                                auth()->id()
                            );
                        }
                    }
                }

                if ($bloqueada->bill && ! $bloqueada->bill->hasPayments()) {
                    $bloqueada->bill->update(['status' => BillStatus::Void->value]);
                }

                $bloqueada->forceFill([
                    'status' => OrderStatus::Cancelled->value,
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => now(),
                    'cancel_reason' => $data['cancel_reason'],
                    'authorized_by' => $admin?->getKey(),
                ])->save();

                $bloqueada->items()
                    ->where('status', '!=', OrderItemStatus::Served->value)
                    ->update(['status' => OrderItemStatus::Cancelled->value]);

                if ($bloqueada->restaurant_table_id) {
                    $mesa = RestaurantTable::query()
                        ->whereKey($bloqueada->restaurant_table_id)
                        ->lockForUpdate()
                        ->first();

                    if ($mesa) {
                        $mesa->setStatus(TableStatus::Cleaning);
                    }
                }
            });
        } catch (ValidationException $e) {
            return redirect()->route('orders.show', $order)
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Throwable $e) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'No se pudo cancelar la orden. Intenta de nuevo.')
                ->withInput();
        }

        return redirect()->route('orders.show', $order)
            ->with('success', 'Orden '.$order->order_number.' cancelada correctamente.');
    }

    /**
     * Añade items a una orden existente y recalcula los totales.
     */
    public function addItems(Request $request, Order $order): RedirectResponse
    {
        if (! $order->isActive()) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'No se pueden añadir items a una orden servida o cancelada.');
        }

        $items = $this->validarItems($request);

        DB::beginTransaction();

        try {
            $this->crearItems($order, $items);

            $order->recalculateTotals();

            DB::commit();
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()->route('orders.show', $order)
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->route('orders.show', $order)
                ->with('error', 'No se pudieron añadir los items. Intenta de nuevo.')
                ->withInput();
        }

        return redirect()->route('orders.show', $order)
            ->with('success', 'Items añadidos a la orden correctamente.');
    }

    /**
     * Cambio rápido del estado individual de un item.
     */
    public function updateItemStatus(Request $request, OrderItem $orderItem): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(OrderItemStatus::options()))],
        ], [
            'status.required' => 'Debes elegir un estado para el item.',
            'status.in' => 'El estado elegido no es válido.',
        ], [
            'status' => 'estado',
        ]);

        $nuevo = OrderItemStatus::from($request->input('status'));
        $anterior = $orderItem->status;

        // Al iniciar la preparación se descuenta la receta del inventario.
        if ($anterior === OrderItemStatus::Pending && $nuevo === OrderItemStatus::Preparing) {
            try {
                DB::transaction(function () use ($orderItem) {
                    $bloqueado = OrderItem::query()
                        ->whereKey($orderItem->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    $afectados = $this->recipeService->consumeForOrderItem($bloqueado);

                    $bloqueado->update(['status' => OrderItemStatus::Preparing->value]);

                    $this->recipeService->deactivateAffectedMenuItems($afectados);
                });
            } catch (ValidationException $e) {
                return redirect()->back()
                    ->with('error', collect($e->errors())->flatten()->first())
                    ->withInput();
            } catch (\Throwable $e) {
                return redirect()->back()
                    ->with('error', 'No se pudo iniciar la preparación del item. Intenta de nuevo.')
                    ->withInput();
            }

            return redirect()->back()
                ->with('success', 'Item "'.$orderItem->item_name.'" marcado como "'.$nuevo->label().'".');
        }

        $orderItem->update(['status' => $nuevo->value]);

        return redirect()->back()
            ->with('success', 'Item "'.$orderItem->item_name.'" marcado como "'.$nuevo->label().'".');
    }

    public function destroy(Order $order): RedirectResponse
    {
        if ($order->bill()->exists()) {
            return redirect()->route('orders.index')
                ->with('error', 'No se puede eliminar la orden '.$order->order_number.' porque tiene una factura generada.');
        }

        $mesa = $order->table;

        $order->delete();

        if ($mesa && $mesa->status === TableStatus::Occupied) {
            $mesa->setStatus(TableStatus::Available);
        }

        return redirect()->route('orders.index')->with('success', 'Orden eliminada correctamente.');
    }

    /**
     * Estados permitidos en los formularios de edición.
     * La cancelación solo se realiza con la acción dedicada.
     */
    private function estadosDisponibles(Order $order): array
    {
        if ($order->status === OrderStatus::Cancelled) {
            return [OrderStatus::Cancelled->value => OrderStatus::Cancelled->label()];
        }

        $estados = OrderStatus::options();
        unset($estados[OrderStatus::Cancelled->value]);

        return $estados;
    }

    /**
     * Reglas de validación de la cabecera de la orden.
     */
    private function validarCabecera(Request $request, ?Order $order = null): array
    {
        $data = $request->validate([
            'source' => ['required', 'in:'.implode(',', array_keys(OrderSource::options()))],
            'restaurant_table_id' => ['nullable', 'exists:restaurant_tables,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'status' => ['nullable', 'in:'.implode(',', array_keys(OrderStatus::options()))],
            'notes' => ['nullable', 'string'],
        ], [
            'source.required' => 'Debes elegir el origen de la orden.',
            'source.in' => 'El origen elegido no es válido.',
            'restaurant_table_id.exists' => 'La mesa seleccionada no existe.',
            'customer_id.exists' => 'El cliente seleccionado no existe.',
            'employee_id.exists' => 'El empleado seleccionado no existe.',
            'status.in' => 'El estado elegido no es válido.',
        ], [
            'source' => 'origen',
            'restaurant_table_id' => 'mesa',
            'customer_id' => 'cliente',
            'employee_id' => 'empleado',
            'status' => 'estado',
            'notes' => 'notas',
        ]);

        $data['status'] = ($data['status'] ?? null) ?: ($order?->status->value ?? OrderStatus::Open->value);
        $data['restaurant_table_id'] = ! empty($data['restaurant_table_id']) ? (int) $data['restaurant_table_id'] : null;
        $data['customer_id'] = ! empty($data['customer_id']) ? (int) $data['customer_id'] : null;
        $data['employee_id'] = ! empty($data['employee_id']) ? (int) $data['employee_id'] : null;
        $data['notes'] = (($data['notes'] ?? null) ?: null);

        // RN-06: la cancelación debe registrarse con su acción dedicada (motivo y PIN).
        if ($data['status'] === OrderStatus::Cancelled->value
            && (! $order || $order->status !== OrderStatus::Cancelled)) {
            throw ValidationException::withMessages([
                'status' => 'Para cancelar una orden debes usar la acción "Cancelar orden" e indicar el motivo.',
            ]);
        }

        if ($order && $order->status === OrderStatus::Cancelled
            && $data['status'] !== OrderStatus::Cancelled->value) {
            throw ValidationException::withMessages([
                'status' => 'Una orden cancelada no puede cambiar de estado.',
            ]);
        }

        // RN-03: Una mesa en occupied, cleaning o maintenance no puede asignarse a una nueva orden.
        if ($data['restaurant_table_id']) {
            $esNuevaAsignacion = ! $order || $order->restaurant_table_id !== $data['restaurant_table_id'];

            if ($esNuevaAsignacion) {
                $mesa = RestaurantTable::find($data['restaurant_table_id']);

                if ($mesa && ! $mesa->isAssignableToOrder()) {
                    throw ValidationException::withMessages([
                        'restaurant_table_id' => sprintf(
                            'La mesa %s no puede asignarse porque se encuentra en estado "%s" (no disponible para nuevas órdenes).',
                            $mesa->table_number,
                            $mesa->status->label()
                        ),
                    ]);
                }
            }
        }

        return $data;
    }

    /**
     * Reglas de validación de los items enviados desde el POS.
     */
    private function validarItems(Request $request): array
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
        ], [
            'items.required' => 'Debes agregar al menos un plato a la orden.',
            'items.min' => 'Debes agregar al menos un plato a la orden.',
            'items.*.menu_item_id.required' => 'Cada item debe tener un plato seleccionado.',
            'items.*.menu_item_id.exists' => 'Uno de los platos seleccionados no existe.',
            'items.*.quantity.required' => 'Cada item debe tener una cantidad.',
            'items.*.quantity.min' => 'La cantidad de cada item debe ser al menos 1.',
        ], [
            'items' => 'items',
            'items.*.menu_item_id' => 'plato',
            'items.*.quantity' => 'cantidad',
            'items.*.notes' => 'notas',
        ]);

        return $data['items'];
    }

    /**
     * Crea los OrderItem de la orden copiando nombre y precio del plato.
     */
    private function crearItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $plato = MenuItem::find($item['menu_item_id']);

            if (! $plato) {
                continue;
            }

            $cantidad = (int) $item['quantity'];

            // RN-05: no se puede agregar un plato si la receta no tiene stock suficiente.
            $this->recipeService->assertStock($plato, $cantidad);

            $orderItem = new OrderItem([
                'order_id' => $order->id,
                'menu_item_id' => $plato->id,
                'item_name' => $plato->name,
                'quantity' => $cantidad,
                'unit_price' => $plato->price,
                'notes' => (($item['notes'] ?? null) ?: null),
                'status' => OrderItemStatus::Pending->value,
            ]);

            $orderItem->recalculate();

            $orderItem->save();
        }
    }
}
