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
use Illuminate\Validation\ValidationException;

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
            'porcentajeServicio' => $ordenSel
                ? Bill::defaultServiceChargePercent($ordenSel->source)
                : 0.0,
            'porcentajeMaximo' => Bill::maxServiceChargePercent(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id', 'unique:bills,order_id'],
            'service_charge_percent' => ['nullable', 'numeric', 'min:0', 'max:'.Bill::maxServiceChargePercent()],
            'service_charge_waived' => ['nullable', 'boolean'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ], [
            'order_id.required' => 'Debes elegir una orden para facturar.',
            'order_id.exists' => 'La orden seleccionada no existe.',
            'order_id.unique' => 'Esta orden ya tiene una factura generada.',
            'service_charge_percent.min' => 'El cargo por servicio no puede ser negativo.',
            'service_charge_percent.max' => 'El cargo por servicio no puede superar el '.pct(Bill::maxServiceChargePercent()).'.',
            'discount.min' => 'El descuento no puede ser negativo.',
        ], [
            'order_id' => 'orden',
            'service_charge_percent' => 'porcentaje de cargo por servicio',
            'service_charge_waived' => 'exención del cargo por servicio',
            'discount' => 'descuento',
        ]);

        $orden = Order::with('items')->findOrFail($data['order_id']);

        $consumo = (float) $orden->total;
        $descuento = (float) ($data['discount'] ?? 0);

        if ($descuento > $consumo) {
            throw ValidationException::withMessages([
                'discount' => 'El descuento no puede superar el consumo de la orden ('.money($consumo).').',
            ]);
        }

        $exento = $request->boolean('service_charge_waived');
        $porcentaje = $exento
            ? 0.0
            : ($request->filled('service_charge_percent')
                ? (float) $data['service_charge_percent']
                : Bill::defaultServiceChargePercent($orden->source));

        $importes = Bill::breakdown($consumo, $descuento, $porcentaje, $exento);

        $factura = Bill::create([
            'order_id' => $orden->id,
            'customer_id' => $orden->customer_id,
            'subtotal' => $importes['subtotal'],
            'tax' => $importes['tax'],
            'service_charge' => $importes['service_charge'],
            'service_charge_percent' => $importes['service_charge_percent'],
            'service_charge_waived' => $importes['service_charge_waived'],
            'discount' => $importes['discount'],
            'total' => $importes['total'],
            'status' => BillStatus::Pending->value,
        ]);

        if ((float) $factura->total <= 0) {
            $factura->update(['status' => BillStatus::Paid->value]);
            $this->liberarMesa($factura);
        }

        return redirect()->route('bills.show', $factura)
            ->with('success', 'Factura '.$factura->bill_number.' generada correctamente.');
    }

    /**
     * Ajusta descuento y cargo por servicio antes del primer pago.
     */
    public function update(Request $request, Bill $bill): RedirectResponse
    {
        if (! $bill->isAdjustable()) {
            return redirect()->route('bills.show', $bill)
                ->with('error', 'La factura ya tiene pagos registrados o no está pendiente; no es posible ajustar el descuento ni el cargo por servicio.');
        }

        $data = $request->validate([
            'service_charge_percent' => ['nullable', 'numeric', 'min:0', 'max:'.Bill::maxServiceChargePercent()],
            'service_charge_waived' => ['nullable', 'boolean'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ], [
            'service_charge_percent.min' => 'El cargo por servicio no puede ser negativo.',
            'service_charge_percent.max' => 'El cargo por servicio no puede superar el '.pct(Bill::maxServiceChargePercent()).'.',
            'discount.min' => 'El descuento no puede ser negativo.',
        ], [
            'service_charge_percent' => 'porcentaje de cargo por servicio',
            'service_charge_waived' => 'exención del cargo por servicio',
            'discount' => 'descuento',
        ]);

        $consumo = $bill->consumption();
        $descuento = (float) ($data['discount'] ?? $bill->discount);

        if ($descuento > $consumo) {
            throw ValidationException::withMessages([
                'discount' => 'El descuento no puede superar el consumo de la orden ('.money($consumo).').',
            ]);
        }

        $exento = $request->boolean('service_charge_waived');
        $porcentaje = $exento
            ? 0.0
            : ($request->filled('service_charge_percent')
                ? (float) $data['service_charge_percent']
                : (float) $bill->service_charge_percent);

        $importes = $bill->calculateAmounts($descuento, $porcentaje, $exento);

        $bill->update([
            'subtotal' => $importes['subtotal'],
            'tax' => $importes['tax'],
            'service_charge' => $importes['service_charge'],
            'service_charge_percent' => $importes['service_charge_percent'],
            'service_charge_waived' => $importes['service_charge_waived'],
            'discount' => $importes['discount'],
            'total' => $importes['total'],
        ]);

        if ((float) $bill->total <= 0) {
            $bill->update(['status' => BillStatus::Paid->value]);
            $this->liberarMesa($bill);
        }

        return redirect()->route('bills.show', $bill)
            ->with('success', 'Ajustes de la factura actualizados correctamente.');
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
        if ($bill->isVoid() || $bill->isCancelled()) {
            return redirect()->route('bills.show', $bill)
                ->with('error', 'La factura '.$bill->bill_number.' está '.mb_strtolower($bill->status->label()).' y no admite pagos.');
        }

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
     * Envía la mesa de la orden a limpieza al quedar la factura pagada.
     */
    private function liberarMesa(Bill $bill): void
    {
        $orden = $bill->order;

        if (! $orden || ! $orden->restaurant_table_id) {
            return;
        }

        $mesa = RestaurantTable::find($orden->restaurant_table_id);

        if (! $mesa || $mesa->status === TableStatus::Maintenance) {
            return;
        }

        if ($mesa->status === TableStatus::Cleaning) {
            if (! $mesa->cleaning_started_at) {
                $mesa->forceFill(['cleaning_started_at' => now()])->save();
            }

            return;
        }

        $mesa->setStatus(TableStatus::Cleaning);
    }
}
