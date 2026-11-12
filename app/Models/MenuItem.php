<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
        'image',
        'ingredients',
        'is_available',
        'is_featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'ingredients' => 'array',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function hasRecipe(): bool
    {
        return $this->recipeItems()->exists();
    }

    /**
     * Convierte el listado por comas en un array y lo persiste en JSON.
     */
    public function setIngredientsAttribute($value): void
    {
        if (is_string($value)) {
            $value = array_values(array_filter(array_map('trim', explode(',', $value)), fn ($v) => $v !== ''));
        }

        $this->attributes['ingredients'] = $value === null ? null : json_encode($value);
    }

    public function ingredientsList(): array
    {
        return $this->ingredients ?? [];
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        return $query->where('name', 'like', '%'.trim($term).'%');
    }

    public function scopeCategory(Builder $query, int|string|null $categoryId): Builder
    {
        if (! $categoryId) {
            return $query;
        }

        return $query->where('category_id', $categoryId);
    }

    public function scopeAvailability(Builder $query, ?string $filter): Builder
    {
        return match ($filter) {
            'available' => $query->where('is_available', true),
            'unavailable' => $query->where('is_available', false),
            default => $query,
        };
    }

    public function scopeFeatured(Builder $query, ?string $filter): Builder
    {
        return $filter === '1' ? $query->where('is_featured', true) : $query;
    }
}
