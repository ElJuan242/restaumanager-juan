<?php

namespace App\Http\Controllers;

use App\Enums\OrderItemStatus;
use App\Models\Order;

class KitchenController extends Controller
{
    /**
     * Vista de cocina: órdenes activas con sus items.
     */
    public function index()
    {
        $registros = Order::with(['table', 'items'])
            ->active()
            ->orderBy('created_at')
            ->get();

        return view('orders.kitchen', [
            'registros' => $registros,
            'estadosItem' => OrderItemStatus::options(),
        ]);
    }
}
