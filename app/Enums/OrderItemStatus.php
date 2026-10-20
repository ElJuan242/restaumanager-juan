<?php

namespace App\Enums;

enum OrderItemStatus: string
{
    case Pending = 'pending';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Served = 'served';
    case Cancelled = 'cancelled';

    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Preparing => 'Preparando',
            self::Ready => 'Listo',
            self::Served => 'Servido',
            self::Cancelled => 'Cancelado',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'bg-secondary',
            self::Preparing => 'bg-warning text-dark',
            self::Ready => 'bg-primary',
            self::Served => 'bg-success',
            self::Cancelled => 'bg-danger',
        };
    }
}
