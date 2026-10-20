<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Transfer = 'transfer';
    case Other = 'other';

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
            self::Cash => 'Efectivo',
            self::Card => 'Tarjeta',
            self::Transfer => 'Transferencia',
            self::Other => 'Otro',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Cash => 'bg-success',
            self::Card => 'bg-primary',
            self::Transfer => 'bg-info text-dark',
            self::Other => 'bg-secondary',
        };
    }

    public function requiresReference(): bool
    {
        return in_array($this, [self::Card, self::Transfer], true);
    }
}
