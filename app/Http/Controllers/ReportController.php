<?php

namespace App\Http\Controllers;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Panel de reportes con KPIs, ventas, top de platos, pagos y reservas.
     */
    public function index(Request $request)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ], [], [
            'from' => 'fecha inicial',
            'to' => 'fecha final',
        ]);

        $zona = 'America/Bogota';

        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'), $zona)->startOfDay()
            : today($zona)->subDays(29)->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'), $zona)->endOfDay()
            : today($zona)->endOfDay();

        // La fecha inicial debe ser estrictamente anterior a la fecha final.
        if ($from->toDateString() >= $to->toDateString()) {
            return redirect()
                ->route('reports.index')
                ->withErrors(['from' => 'La fecha inicial debe ser anterior a la fecha final.'])
                ->withInput();
        }

        // El rango máximo permitido es de 365 días.
        if ($from->diffInDays($to->copy()->startOfDay()) > 365) {
            return redirect()
                ->route('reports.index')
                ->withErrors(['to' => 'El rango de fechas no puede superar los 365 días.'])
                ->withInput();
        }

        $desde = $from->toDateString();
        $hasta = $to->toDateString();

        // ---------- KPIs de ventas (facturas pagadas) ----------
        $facturas = DB::table('bills')
            ->where('status', 'paid')
            ->whereBetween(DB::raw('DATE(created_at)'), [$desde, $hasta]);

        $totalVentas = (float) (clone $facturas)->sum('total');
        $cantidadFacturas = (int) (clone $facturas)->count();
        $ventaPromedio = $cantidadFacturas > 0 ? $totalVentas / $cantidadFacturas : 0;

        // ---------- Órdenes del rango ----------
        $cantidadOrdenes = (int) DB::table('orders')
            ->whereBetween(DB::raw('DATE(created_at)'), [$desde, $hasta])
            ->count();

        $totalOrdenesValor = (float) DB::table('orders')
            ->whereBetween(DB::raw('DATE(created_at)'), [$desde, $hasta])
            ->sum('total');

        $valorPromedioOrden = $cantidadOrdenes > 0 ? $totalOrdenesValor / $cantidadOrdenes : 0;

        // ---------- Top 10 platos más vendidos ----------
        $topPlatos = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$desde, $hasta])
            ->whereNotIn('orders.id', function ($query) {
                $query->select('order_id')->from('bills')->where('status', 'void');
            })
            ->selectRaw('order_items.item_name, SUM(order_items.quantity) as total_qty, SUM(order_items.subtotal) as total_ingresos')
            ->groupBy('order_items.item_name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        // ---------- Desglose diario de ventas ----------
        $ventasDiarias = DB::table('bills')
            ->where('status', 'paid')
            ->whereBetween(DB::raw('DATE(created_at)'), [$desde, $hasta])
            ->selectRaw('DATE(created_at) as dia, SUM(total) as total, COUNT(*) as cantidad')
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        // ---------- Distribución por método de pago ----------
        $pagosRaw = DB::table('payments')
            ->join('bills', 'bills.id', '=', 'payments.bill_id')
            ->where('bills.status', '!=', 'void')
            ->whereBetween(DB::raw('DATE(payments.created_at)'), [$desde, $hasta])
            ->selectRaw('payments.method, SUM(payments.amount) as total, COUNT(*) as cantidad')
            ->groupBy('payments.method')
            ->orderByDesc('total')
            ->get();

        $totalRecaudado = (float) $pagosRaw->sum('total');

        $pagos = $pagosRaw->map(function ($fila) use ($totalRecaudado) {
            $metodo = PaymentMethod::tryFrom((string) $fila->method);
            $total = (float) $fila->total;

            return (object) [
                'method' => $fila->method,
                'metodo' => $metodo,
                'label' => $metodo ? $metodo->label() : (string) $fila->method,
                'badge' => $metodo ? $metodo->badge() : 'bg-secondary',
                'total' => $total,
                'cantidad' => (int) $fila->cantidad,
                'porcentaje' => $totalRecaudado > 0 ? round(($total / $totalRecaudado) * 100, 1) : 0,
            ];
        });

        // ---------- Reservas ----------
        $totalReservas = (int) DB::table('reservations')
            ->whereNull('deleted_at')
            ->whereBetween(DB::raw('DATE(reservation_date)'), [$desde, $hasta])
            ->count();

        $reservasConfirmadas = (int) DB::table('reservations')
            ->whereNull('deleted_at')
            ->where('status', 'confirmed')
            ->whereBetween(DB::raw('DATE(reservation_date)'), [$desde, $hasta])
            ->count();

        // ---------- Órdenes por origen y por estado ----------
        $ordenesPorOrigenRaw = DB::table('orders')
            ->whereBetween(DB::raw('DATE(created_at)'), [$desde, $hasta])
            ->selectRaw('source, COUNT(*) as cantidad, SUM(total) as total')
            ->groupBy('source')
            ->get();

        $ordenesPorOrigen = $ordenesPorOrigenRaw->map(function ($fila) {
            $origen = OrderSource::tryFrom((string) $fila->source);

            return (object) [
                'clave' => $fila->source,
                'label' => $origen ? $origen->label() : (string) $fila->source,
                'badge' => $origen ? $origen->badge() : 'bg-secondary',
                'cantidad' => (int) $fila->cantidad,
                'total' => (float) $fila->total,
            ];
        });

        $ordenesPorEstadoRaw = DB::table('orders')
            ->whereBetween(DB::raw('DATE(created_at)'), [$desde, $hasta])
            ->selectRaw('status, COUNT(*) as cantidad, SUM(total) as total')
            ->groupBy('status')
            ->get();

        $ordenesPorEstado = $ordenesPorEstadoRaw->map(function ($fila) {
            $estado = OrderStatus::tryFrom((string) $fila->status);

            return (object) [
                'clave' => $fila->status,
                'label' => $estado ? $estado->label() : (string) $fila->status,
                'badge' => $estado ? $estado->badge() : 'bg-secondary',
                'cantidad' => (int) $fila->cantidad,
                'total' => (float) $fila->total,
            ];
        });

        $hayDatos = $cantidadFacturas > 0
            || $cantidadOrdenes > 0
            || $totalReservas > 0
            || $pagos->isNotEmpty();

        return view('reports.index', [
            'from' => $desde,
            'to' => $hasta,
            'totalVentas' => $totalVentas,
            'cantidadFacturas' => $cantidadFacturas,
            'ventaPromedio' => $ventaPromedio,
            'cantidadOrdenes' => $cantidadOrdenes,
            'valorPromedioOrden' => $valorPromedioOrden,
            'totalOrdenesValor' => $totalOrdenesValor,
            'topPlatos' => $topPlatos,
            'ventasDiarias' => $ventasDiarias,
            'pagos' => $pagos,
            'totalRecaudado' => $totalRecaudado,
            'totalReservas' => $totalReservas,
            'reservasConfirmadas' => $reservasConfirmadas,
            'ordenesPorOrigen' => $ordenesPorOrigen,
            'ordenesPorEstado' => $ordenesPorEstado,
            'hayDatos' => $hayDatos,
        ]);
    }
}
