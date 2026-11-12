<?php

namespace App\Enums;

enum BillStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Void = 'void';

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
            self::Partial => 'Parcial',
            self::Paid => 'Pagada',
            self::Cancelled => 'Cancelada',
            self::Void => 'Anulada',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'bg-warning text-dark',
            self::Partial => 'bg-info text-dark',
            self::Paid => 'bg-success',
            self::Cancelled => 'bg-danger',
            self::Void => 'bg-dark',
        };
    }
}
