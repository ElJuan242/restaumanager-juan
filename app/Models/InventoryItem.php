<?php

namespace App\Models;

use App\Enums\InventoryCategory;
use App\Enums\InventoryMovementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventory';

    protected $fillable = [
        'name',
        'description',
        'category',
        'quantity',
        'unit',
        'price',
        'min_stock',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'price' => 'float',
            'min_stock' => 'float',
            'active' => 'boolean',
        ];
    }

    public function categoryEnum(): ?InventoryCategory
    {
        return InventoryCategory::tryFrom((string) $this->category);
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->min_stock;
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(RecipeItem::class, 'inventory_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'inventory_id');
    }

    /**
     * Registra un movimiento usando el saldo actual del insumo.
     */
    public function recordMovement(
        InventoryMovementType $type,
        float $quantity,
        ?string $reason = null,
        ?Model $reference = null,
        ?int $userId = null
    ): InventoryMovement {
        return $this->movements()->create([
            'type' => $type->value,
            'quantity' => round($quantity, 2),
            'balance_after' => round((float) $this->quantity, 2),
            'user_id' => $userId,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'reason' => $reason,
        ]);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '<=', 'min_stock');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        return $query->where('name', 'like', '%'.trim($term).'%');
    }

    public function scopeCategory(Builder $query, ?string $category): Builder
    {
        if ($category === null || $category === '') {
            return $query;
        }

        return $query->where('category', $category);
    }

    /**
     * Suma o resta cantidad sin permitir stock negativo.
     */
    public function adjustQuantity(float $delta): void
    {
        $new = round($this->quantity + $delta, 2);

        $this->forceFill(['quantity' => max(0, $new)])->save();
    }
}
