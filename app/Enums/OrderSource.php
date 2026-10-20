<?php

namespace App\Enums;

enum OrderSource: string
{
    case DineIn = 'dine_in';
    case Takeaway = 'takeaway';
    case Delivery = 'delivery';

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
            self::DineIn => 'En restaurante',
            self::Takeaway => 'Para llevar',
            self::Delivery => 'Domicilio',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::DineIn => 'bg-primary',
            self::Takeaway => 'bg-info text-dark',
            self::Delivery => 'bg-dark',
        };
    }

    public function requiresTable(): bool
    {
        return $this === self::DineIn;
    }
}
