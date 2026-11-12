<?php

namespace App\Models;

use App\Enums\BillStatus;
use App\Enums\OrderSource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_number',
        'order_id',
        'customer_id',
        'subtotal',
        'tax',
        'service_charge',
        'service_charge_percent',
        'service_charge_waived',
        'discount',
        'total',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'float',
            'tax' => 'float',
            'service_charge' => 'float',
            'service_charge_percent' => 'float',
            'service_charge_waived' => 'boolean',
            'discount' => 'float',
            'total' => 'float',
            'status' => BillStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (! $model->bill_number) {
                $model->bill_number = next_document_number('FAC', 'bill_number', 'bills');
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paidAmount(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function balance(): float
    {
        return round($this->total - $this->paidAmount(), 2);
    }

    public function isFullyPaid(): bool
    {
        return $this->paidAmount() >= $this->total;
    }

    public function hasPayments(): bool
    {
        if ($this->relationLoaded('payments')) {
            return $this->payments->isNotEmpty();
        }

        return $this->payments()->exists();
    }

    /**
     * Consumo facturado: total bruto de la orden (precios con IVA incluido).
     */
    public function consumption(): float
    {
        $orden = $this->order;

        if ($orden) {
            return round((float) $orden->total, 2);
        }

        return round((float) $this->subtotal + (float) $this->tax, 2);
    }

    /**
     * Porcentaje máximo de cargo por servicio permitido.
     */
    public static function maxServiceChargePercent(): float
    {
        return round((float) config('restauranager.service_charge_rate', 0.10) * 100, 2);
    }

    /**
     * Porcentaje sugerido según el origen de la orden.
     */
    public static function defaultServiceChargePercent(?OrderSource $source = null): float
    {
        return $source === OrderSource::DineIn ? self::maxServiceChargePercent() : 0.0;
    }

    /**
     * Calcula los importes de la factura a partir del consumo bruto.
     *
     * El IVA está incluido en el consumo. El descuento reduce la base y el
     * cargo por servicio se calcula sobre esa base sin IVA adicional.
     */
    public static function breakdown(
        float $consumption,
        float $discount = 0,
        float $serviceChargePercent = 0,
        bool $serviceChargeWaived = false
    ): array {
        $taxRate = (float) config('restauranager.tax_rate', 0.19);

        $consumption = max(0, round($consumption, 2));
        $discount = min(max(0, round($discount, 2)), $consumption);
        $base = round($consumption - $discount, 2);

        $subtotal = round($base / (1 + $taxRate));
        $tax = round($base - $subtotal, 2);

        $percent = $serviceChargeWaived
            ? 0.0
            : min(max(0, round($serviceChargePercent, 2)), self::maxServiceChargePercent());

        $serviceCharge = round($base * $percent / 100, 2);
        $total = round($base + $serviceCharge, 2);

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'service_charge' => $serviceCharge,
            'service_charge_percent' => $percent,
            'service_charge_waived' => $serviceChargeWaived,
            'discount' => $discount,
            'total' => $total,
        ];
    }

    /**
     * Importes calculados con los valores actuales o con los recibidos.
     */
    public function calculateAmounts(
        ?float $discount = null,
        ?float $serviceChargePercent = null,
        ?bool $serviceChargeWaived = null
    ): array {
        return self::breakdown(
            $this->consumption(),
            $discount ?? (float) $this->discount,
            $serviceChargePercent ?? (float) $this->service_charge_percent,
            $serviceChargeWaived ?? (bool) $this->service_charge_waived,
        );
    }

    /**
     * Solo se puede ajustar el descuento y el cargo por servicio antes del primer pago.
     */
    public function isAdjustable(): bool
    {
        return $this->status === BillStatus::Pending && ! $this->hasPayments();
    }

    public function isPayable(): bool
    {
        return ! in_array($this->status, [BillStatus::Paid, BillStatus::Cancelled, BillStatus::Void], true)
            && ! $this->isFullyPaid();
    }

    public function isVoid(): bool
    {
        return $this->status === BillStatus::Void;
    }

    public function isCancelled(): bool
    {
        return $this->status === BillStatus::Cancelled;
    }

    public function isPaid(): bool
    {
        return $this->status === BillStatus::Paid;
    }

    /**
     * Cargo por servicio (10% por defecto) como tasa decimal.
     */
    public static function defaultServiceCharge(): float
    {
        return (float) config('restauranager.service_charge_rate', 0.10);
    }
}
