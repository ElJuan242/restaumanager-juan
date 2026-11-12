<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tasa de IVA
    |--------------------------------------------------------------------------
    |
    | Impuesto aplicado a las órdenes de venta y a las órdenes de compra.
    |
    */

    'tax_rate' => (float) env('TAX_RATE', 0.19),

    /*
    |--------------------------------------------------------------------------
    | Cargo por servicio
    |--------------------------------------------------------------------------
    |
    | Porcentaje de cargo por servicio aplicado por defecto a las facturas.
    | 'service_charge_rate' es la clave principal y 'service_charge' se
    | mantiene como alias para compatibilidad retrospectiva.
    |
    */

    'service_charge_rate' => (float) env('SERVICE_CHARGE_RATE', 0.10),
    'service_charge' => (float) env('SERVICE_CHARGE_RATE', 0.10),

    /*
    |--------------------------------------------------------------------------
    | Duración estimada de reservas (minutos)
    |--------------------------------------------------------------------------
    |
    | Tiempo estándar de permanencia que se asigna a una reserva de mesa.
    |
    */

    'reservation_duration_minutes' => (int) env('RESERVATION_DURATION_MINUTES', 120),

    /*
    |--------------------------------------------------------------------------
    | Margen de rotación entre reservas (minutos)
    |--------------------------------------------------------------------------
    |
    | Margen de tiempo para limpieza y preparación de la mesa antes del
    | siguiente servicio o turno de reserva.
    |
    */

    'reservation_turnover_buffer_minutes' => (int) env('RESERVATION_TURNOVER_BUFFER_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Días de vencimiento de órdenes de compra
    |--------------------------------------------------------------------------
    |
    | Número de días tras los cuales una orden de compra pendiente se
    | considera vencida o retrasada si no ha sido recibida.
    |
    */

    'po_overdue_days' => (int) env('PO_OVERDUE_DAYS', 15),

];
