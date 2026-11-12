<?php

namespace App\Models;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'restaurant_table_id',
        'customer_id',
        'employee_id',
        'status',
        'source',
        'notes',
        'subtotal',
        'tax',
        'total',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
        'authorized_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'source' => OrderSource::class,
            'subtotal' => 'float',
            'tax' => 'float',
            'total' => 'float',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (! $model->order_number) {
                $model->order_number = next_document_number('ORD', 'order_number', 'orders');
            }
        });
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function bill(): HasOne
    {
        return $this->hasOne(Bill::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function taxRate(): float
    {
        return (float) config('restauranager.tax_rate', 0.19);
    }

    /**
     * Recalcula los totales a partir de los items.
     *
     * El precio del menú es precio final con IVA incluido:
     * total = suma de (precio x cantidad); subtotal = total / (1 + IVA); IVA = total - subtotal.
     */
    public function recalculateTotals(): void
    {
        $total = round((float) $this->items()->sum('subtotal'), 2);
        $subtotal = round($total / (1 + $this->taxRate()));
        $tax = round($total - $subtotal, 2);

        $this->forceFill([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ])->save();
    }

    public function isActive(): bool
    {
        return ! $this->status->isClosable();
    }

    /**
     * Al servir la orden se libera la mesa asociada.
     */
    public function releaseTable(): void
    {
        if ($this->restaurant_table_id && $this->status->isClosable()) {
            RestaurantTable::where('id', $this->restaurant_table_id)
                ->where('status', TableStatus::Occupied->value)
                ->update(['status' => TableStatus::Available->value]);
        }
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        return $query->where('order_number', 'like', '%'.trim($term).'%');
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if ($status === null || $status === '') {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function scopeTable(Builder $query, ?int $tableId): Builder
    {
        if (! $tableId) {
            return $query;
        }

        return $query->where('restaurant_table_id', $tableId);
    }

    public function scopeSource(Builder $query, ?string $source): Builder
    {
        if ($source === null || $source === '') {
            return $query;
        }

        return $query->where('source', $source);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            OrderStatus::Open->value,
            OrderStatus::Preparing->value,
            OrderStatus::Ready->value,
        ]);
    }
}
