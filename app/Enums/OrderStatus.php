<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Open = 'open';
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
            self::Open => 'Abierta',
            self::Preparing => 'Preparando',
            self::Ready => 'Lista',
            self::Served => 'Servida',
            self::Cancelled => 'Cancelada',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Open => 'bg-info text-dark',
            self::Preparing => 'bg-warning text-dark',
            self::Ready => 'bg-primary',
            self::Served => 'bg-success',
            self::Cancelled => 'bg-danger',
        };
    }

    public function isClosable(): bool
    {
        return in_array($this, [self::Served, self::Cancelled], true);
    }
}
