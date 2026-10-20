<?php

namespace App\Http\Controllers;

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

        return view('dashboard.index', [
            // Tarjetas principales
            'totalInventario' => InventoryItem::active()->count(),
            'stockBajo' => InventoryItem::active()->lowStock()->count(),
            'reservasHoy' => Reservation::whereDate('reservation_date', today())
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
                ->where('reservation_date', '>=', now()->startOfDay())
                ->whereNotIn('status', ['cancelled', 'completed'])
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
