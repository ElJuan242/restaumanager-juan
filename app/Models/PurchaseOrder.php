<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'order_date',
        'expected_date',
        'received_date',
        'status',
        'subtotal',
        'tax',
        'total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_date' => 'date',
            'received_date' => 'date',
            'status' => PurchaseOrderStatus::class,
            'subtotal' => 'float',
            'tax' => 'float',
            'total' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (! $model->po_number) {
                $model->po_number = next_document_number('PO', 'po_number', 'purchase_orders');
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function taxRate(): float
    {
        return (float) config('restauranager.tax_rate', 0.19);
    }

    public function isLocked(): bool
    {
        return $this->status->isLocked();
    }

    /**
     * Recalcula subtotal + IVA 19% = total.
     */
    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('subtotal');
        $tax = round($subtotal * $this->taxRate(), 2);

        $this->forceFill([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $subtotal + $tax,
        ])->save();
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        return $query->where('po_number', 'like', '%'.trim($term).'%');
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if ($status === null || $status === '') {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function scopeSupplier(Builder $query, ?int $supplierId): Builder
    {
        if (! $supplierId) {
            return $query;
        }

        return $query->where('supplier_id', $supplierId);
    }
}
