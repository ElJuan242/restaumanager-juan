<?php

namespace App\Enums;

enum TableStatus: string
{
    case Available = 'available';
    case Occupied = 'occupied';
    case Reserved = 'reserved';
    case Maintenance = 'maintenance';

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
            self::Available => 'Disponible',
            self::Occupied => 'Ocupada',
            self::Reserved => 'Reservada',
            self::Maintenance => 'Mantenimiento',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Available => 'bg-success',
            self::Occupied => 'bg-danger',
            self::Reserved => 'bg-warning text-dark',
            self::Maintenance => 'bg-secondary',
        };
    }
}
