<?php

namespace App\Http\Controllers;

use App\Enums\TableStatus;
use App\Models\Bill;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $mesasDisponibles = RestaurantTable::active()->available()->count();
        $umbralLimpiezaMinutos = (int) config('restauranager.reservation_turnover_buffer_minutes', 15);

        $mesasLimpiezaRetrasada = RestaurantTable::active()
            ->where('status', TableStatus::Cleaning->value)
            ->whereNotNull('cleaning_started_at')
            ->where('cleaning_started_at', '<=', now()->subMinutes($umbralLimpiezaMinutos))
            ->orderBy('cleaning_started_at')
            ->get();

        return view('dashboard.index', [
            // Alerta de limpieza
            'mesasLimpiezaRetrasada' => $mesasLimpiezaRetrasada,
            'umbralLimpiezaMinutos' => $umbralLimpiezaMinutos,
            'mesasEnLimpieza' => RestaurantTable::active()->where('status', TableStatus::Cleaning->value)->count(),

            // Tarjetas principales
            'totalInventario' => InventoryItem::active()->count(),
            'stockBajo' => InventoryItem::active()->lowStock()->count(),
            'reservasHoy' => Reservation::whereDate('reservation_date', today('America/Bogota'))
                ->whereNotIn('status', ['cancelled'])
                ->count(),
            'totalMesas' => RestaurantTable::active()->count(),
            'mesasDisponibles' => $mesasDisponibles,

            // Tarjetas complementarias
            'ordenesActivas' => Order::active()->count(),
            'ventasHoy' => (float) Bill::whereDate('created_at', today())
                ->where('status', 'paid')
                ->sum('total'),

            // Listas
            'reservasProximas' => Reservation::with('table')
                ->upcoming()
                ->orderBy('reservation_date')
                ->take(6)
                ->get(),

            'alertasStock' => InventoryItem::active()
                ->lowStock()
                ->orderBy('quantity')
                ->take(8)
                ->get(),

            'mesasLibres' => RestaurantTable::active()
                ->available()
                ->orderBy('table_number')
                ->take(8)
                ->get(),

            'ordenesRecientes' => Order::with(['table', 'customer'])
                ->latest()
                ->take(6)
                ->get(),
        ]);
    }
}
