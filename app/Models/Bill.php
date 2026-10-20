<?php

namespace App\Models;

use App\Enums\BillStatus;
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

    /**
     * Cargo por servicio (10% por defecto) sobre el subtotal.
     */
    public static function defaultServiceCharge(): float
    {
        return (float) config('restauranager.service_charge', 0.10);
    }
}
