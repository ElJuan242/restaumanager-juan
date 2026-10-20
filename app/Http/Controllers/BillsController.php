<?php

namespace App\Http\Controllers;

use App\Enums\BillStatus;
use App\Enums\PaymentMethod;
use App\Enums\TableStatus;
use App\Models\Bill;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RestaurantTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillsController extends Controller
{
    /**
     * Listado con filtro de estado y rango de fechas.
     */
    public function index(Request $request)
    {
        $filtros = $request->only(['status', 'from', 'to']);

        $registros = Bill::query()
            ->when($filtros['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filtros['from'] ?? null, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filtros['to'] ?? null, fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->with(['order', 'customer'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('bills.index', [
            'registros' => $registros,
            'estados' => BillStatus::options(),
            'filtros' => $filtros,
        ]);
    }

    /**
     * Genera una factura a partir de una orden sin factura.
     */
    public function create(Request $request)
    {
        $ordenSel = null;

        if ($request->filled('order_id')) {
            $ordenSel = Order::with(['items', 'customer', 'table'])
                ->whereDoesntHave('bill')
                ->find((int) $request->input('order_id'));
        }

        $ordenes = Order::doesntHave('bill')
            ->with(['items', 'customer', 'table'])
            ->latest()
            ->get();

        return view('bills.create', [
            'ordenes' => $ordenes,
            'ordenSel' => $ordenSel,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id', 'unique:bills,order_id'],
            'service_charge' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ], [
            'order_id.required' => 'Debes elegir una orden para facturar.',
            'order_id.exists' => 'La orden seleccionada no existe.',
            'order_id.unique' => 'Esta orden ya tiene una factura generada.',
            'service_charge.min' => 'El cargo por servicio no puede ser negativo.',
            'discount.min' => 'El descuento no puede ser negativo.',
        ], [
            'order_id' => 'orden',
            'service_charge' => 'cargo por servicio',
            'discount' => 'descuento',
        ]);

        $orden = Order::with('items')->findOrFail($data['order_id']);

        $subtotal = (float) $orden->subtotal;
        $tax = (float) $orden->tax;

        $serviceCharge = $request->filled('service_charge')
            ? (float) $data['service_charge']
            : round($subtotal * Bill::defaultServiceCharge(), 2);

        $discount = (float) ($data['discount'] ?? 0);

        $total = round($subtotal + $tax + $serviceCharge - $discount, 2);

        $factura = Bill::create([
            'order_id' => $orden->id,
            'customer_id' => $orden->customer_id,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'service_charge' => $serviceCharge,
            'discount' => $discount,
            'total' => $total,
            'status' => BillStatus::Pending->value,
        ]);

        return redirect()->route('bills.show', $factura)
            ->with('success', 'Factura '.$factura->bill_number.' generada correctamente.');
    }

    public function show(Bill $bill)
    {
        $bill->load(['order.table', 'order.items', 'customer', 'payments']);

        return view('bills.show', [
            'registro' => $bill,
            'metodos' => PaymentMethod::options(),
        ]);
    }

    /**
     * Registra un pago y recalcula el estado de la factura.
     */
    public function pay(Request $request, Bill $bill): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:'.implode(',', array_keys(PaymentMethod::options()))],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ], [
            'amount.required' => 'Debes ingresar el monto del pago.',
            'amount.min' => 'El monto del pago debe ser mayor que cero.',
            'method.required' => 'Debes elegir un método de pago.',
            'method.in' => 'El método de pago elegido no es válido.',
        ], [
            'amount' => 'monto',
            'method' => 'método de pago',
            'reference' => 'referencia',
            'notes' => 'notas',
        ]);

        $estabaPagada = $bill->status === BillStatus::Paid;
        $saldoAnterior = $bill->balance();

        DB::beginTransaction();

        try {
            Payment::create([
                'bill_id' => $bill->id,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'reference' => (($data['reference'] ?? null) ?: null),
                'notes' => (($data['notes'] ?? null) ?: null),
            ]);

            $bill->refresh();

            $pagado = $bill->paidAmount();

            if ($pagado >= $bill->total) {
                $bill->update(['status' => BillStatus::Paid->value]);

                $this->liberarMesa($bill);

                if (! $estabaPagada) {
                    $bill->customer?->registerVisit((float) $bill->total);
                }
            } elseif ($pagado > 0) {
                $bill->update(['status' => BillStatus::Partial->value]);
            } else {
                $bill->update(['status' => BillStatus::Pending->value]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->route('bills.show', $bill)
                ->with('error', 'No se pudo registrar el pago. Intenta de nuevo.');
        }

        $excedente = (float) $data['amount'] > $saldoAnterior;

        if ($bill->isFullyPaid()) {
            $mensaje = 'Pago registrado. La factura '.$bill->bill_number.' quedó totalmente pagada.';

            if ($excedente) {
                return redirect()->route('bills.show', $bill)->with('info', $mensaje.' El pago superó el saldo pendiente.');
            }

            return redirect()->route('bills.show', $bill)->with('success', $mensaje);
        }

        $mensaje = 'Pago registrado. Saldo restante: '.money_exact($bill->balance()).'.';

        if ($excedente) {
            return redirect()->route('bills.show', $bill)->with('info', $mensaje.' El pago superó el saldo pendiente.');
        }

        return redirect()->route('bills.show', $bill)->with('success', $mensaje);
    }

    public function destroy(Bill $bill): RedirectResponse
    {
        if ($bill->payments()->exists()) {
            return redirect()->route('bills.index')
                ->with('error', 'No se puede eliminar la factura '.$bill->bill_number.' porque tiene pagos registrados.');
        }

        $bill->delete();

        return redirect()->route('bills.index')->with('success', 'Factura eliminada correctamente.');
    }

    /**
     * Libera la mesa de la orden al quedar la factura pagada.
     */
    private function liberarMesa(Bill $bill): void
    {
        $orden = $bill->order;

        if (! $orden || ! $orden->restaurant_table_id) {
            return;
        }

        if ($orden->status->isClosable()) {
            $orden->releaseTable();

            return;
        }

        RestaurantTable::where('id', $orden->restaurant_table_id)
            ->where('status', TableStatus::Occupied->value)
            ->update(['status' => TableStatus::Available->value]);
    }
}
