<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
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
        return view('orders.create', [
            'platos' => MenuItem::where('is_available', true)->with('category')->orderBy('name')->get(),
            'mesas' => RestaurantTable::active()->orderBy('table_number')->get(),
            'clientes' => Customer::orderBy('name')->get(),
            'empleados' => Employee::active()->orderBy('name')->get(),
            'origenes' => OrderSource::options(),
            'estados' => OrderStatus::options(),
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
        $order->load(['table', 'customer', 'employee', 'items.menuItem', 'bill']);

        return view('orders.show', [
            'registro' => $order,
            'estadosItem' => OrderItemStatus::options(),
            'estados' => OrderStatus::options(),
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
            'estados' => OrderStatus::options(),
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $this->validarCabecera($request);

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
     * Reglas de validación de la cabecera de la orden.
     */
    private function validarCabecera(Request $request): array
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

        $data['status'] = ($data['status'] ?? null) ?: OrderStatus::Open->value;
        $data['restaurant_table_id'] = ! empty($data['restaurant_table_id']) ? (int) $data['restaurant_table_id'] : null;
        $data['customer_id'] = ! empty($data['customer_id']) ? (int) $data['customer_id'] : null;
        $data['employee_id'] = ! empty($data['employee_id']) ? (int) $data['employee_id'] : null;
        $data['notes'] = (($data['notes'] ?? null) ?: null);

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
