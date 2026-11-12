<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Pending = 'pending';
    case Ordered = 'ordered';
    case Partial = 'partial';
    case Received = 'received';
    case Cancelled = 'cancelled';
    case Closed = 'closed';

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
            self::Ordered => 'Ordenada',
            self::Partial => 'Parcial',
            self::Received => 'Recibida',
            self::Cancelled => 'Cancelada',
            self::Closed => 'Cerrada',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'bg-warning text-dark',
            self::Ordered => 'bg-primary',
            self::Partial => 'bg-info text-dark',
            self::Received => 'bg-success',
            self::Cancelled => 'bg-danger',
            self::Closed => 'bg-dark',
        };
    }

    public function isLocked(): bool
    {
        return in_array($this, [self::Received, self::Cancelled, self::Closed], true);
    }
}
