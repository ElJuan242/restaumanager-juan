<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case SaleConsumption = 'sale_consumption';
    case PurchaseReceipt = 'purchase_receipt';
    case ManualAdjustment = 'manual_adjustment';
    case Waste = 'waste';

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
            self::SaleConsumption => 'Consumo por venta',
            self::PurchaseReceipt => 'Recepción de compra',
            self::ManualAdjustment => 'Ajuste manual',
            self::Waste => 'Merma',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::SaleConsumption => 'bg-danger',
            self::PurchaseReceipt => 'bg-success',
            self::ManualAdjustment => 'bg-info text-dark',
            self::Waste => 'bg-warning text-dark',
        };
    }
}
