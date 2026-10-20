<?php

use Illuminate\Support\Facades\DB;

if (! function_exists('money')) {
    /**
     * Formato monetario en pesos colombianos: $ 1.234.567
     */
    function money($amount): string
    {
        return '$ '.number_format((float) $amount, 0, ',', '.');
    }
}

if (! function_exists('money_exact')) {
    /**
     * Formato monetario con dos decimales: $ 1.234.567,80
     */
    function money_exact($amount): string
    {
        return '$ '.number_format((float) $amount, 2, ',', '.');
    }
}

if (! function_exists('pct')) {
    /**
     * Formato de porcentaje: 19%
     */
    function pct($amount, int $decimals = 0): string
    {
        return rtrim(rtrim(number_format((float) $amount, $decimals, ',', '.'), '0'), ',').'%';
    }
}

if (! function_exists('badge')) {
    /**
     * Devuelve el HTML de un badge para cualquier enum que exponga label() y badge().
     */
    function badge(?object $enum): string
    {
        if ($enum === null || ! $enum instanceof \BackedEnum || ! method_exists($enum, 'label') || ! method_exists($enum, 'badge')) {
            return '';
        }

        return sprintf('<span class="badge %s">%s</span>', $enum->badge(), $enum->label());
    }
}

if (! function_exists('next_document_number')) {
    /**
     * Numeración automática: PREFIJO-YYMMDD-NNN (ej. ORD-261020-001)
     */
    function next_document_number(string $prefix, string $column, string $table): string
    {
        $date = now()->format('ymd');
        $last = DB::table($table)
            ->where($column, 'like', $prefix.'-'.$date.'-%')
            ->orderByDesc($column)
            ->value($column);

        $sequence = 1;

        if (is_string($last) && str_contains($last, '-')) {
            $parts = explode('-', $last);
            $sequence = ((int) end($parts)) + 1;
        }

        return sprintf('%s-%s-%03d', $prefix, $date, $sequence);
    }
}

if (! function_exists('is_stock_low')) {
    /**
     * Detecta stock bajo: la cantidad ya no supera el stock mínimo.
     */
    function is_stock_low(object $item): bool
    {
        return (float) ($item->quantity ?? 0) <= (float) ($item->min_stock ?? 0);
    }
}

if (! function_exists('date_es')) {
    /**
     * Fecha legible en español: 20 de octubre de 2026
     */
    function date_es($value, string $format = 'j \\d\\e F \\d\\e Y'): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $date = $value instanceof \Carbon\CarbonInterface
            ? $value
            : \Carbon\Carbon::parse($value);

        return $date->translatedFormat($format);
    }
}

if (! function_exists('time_es')) {
    /**
     * Hora legible: 7:30 p. m.
     */
    function time_es($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $date = $value instanceof \Carbon\CarbonInterface
            ? $value
            : \Carbon\Carbon::parse($value);

        return $date->translatedFormat('g:i A');
    }
}

if (! function_exists('shift_duration')) {
    /**
     * Duración de un turno en horas y minutos ("7 h 30 min").
     */
    function shift_duration(?string $start, ?string $end): string
    {
        if (! $start || ! $end) {
            return '—';
        }

        $from = \Carbon\Carbon::parse($start);
        $to = \Carbon\Carbon::parse($end);

        if ($to->lessThan($from)) {
            $to->addDay();
        }

        $minutes = (int) $from->diffInMinutes($to);
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return $hours > 0 ? sprintf('%d h %02d min', $hours, $mins) : sprintf('%d min', $mins);
    }
}
