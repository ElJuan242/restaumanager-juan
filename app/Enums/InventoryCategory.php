<?php

namespace App\Enums;

enum InventoryCategory: string
{
    case Carnes = 'Carnes';
    case Verduras = 'Verduras';
    case Granos = 'Granos';
    case Bebidas = 'Bebidas';
    case Lacteos = 'Lacteos';
    case Aceites = 'Aceites';
    case Condimentos = 'Condimentos';
    case Insumos = 'Insumos';
    case General = 'General';

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
            self::Carnes => 'Carnes',
            self::Verduras => 'Verduras',
            self::Granos => 'Granos',
            self::Bebidas => 'Bebidas',
            self::Lacteos => 'Lácteos',
            self::Aceites => 'Aceites',
            self::Condimentos => 'Condimentos',
            self::Insumos => 'Insumos',
            self::General => 'General',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Carnes => 'bg-danger-subtle text-danger border',
            self::Verduras => 'bg-success-subtle text-success border',
            self::Bebidas => 'bg-info-subtle text-info border',
            self::Lacteos => 'bg-primary-subtle text-primary border',
            default => 'bg-secondary-subtle text-secondary border',
        };
    }
}
