<?php

namespace App\Enums;

enum TableLocation: string
{
    case SalonPrincipal = 'Salon Principal';
    case Terraza = 'Terraza';
    case VIP = 'VIP';
    case Barra = 'Barra';
    case SalonPrivado = 'Salon Privado';
    case Exterior = 'Exterior';

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
        return $this->value;
    }

    public function badge(): string
    {
        return match ($this) {
            self::VIP, self::SalonPrivado => 'bg-purple',
            self::Terraza, self::Exterior => 'bg-info text-dark',
            self::Barra => 'bg-dark',
            default => 'bg-light text-dark border',
        };
    }
}
