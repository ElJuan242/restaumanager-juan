<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MenuCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $base = Str::slug((string) $model->name);

            if ($base === '') {
                $base = 'categoria';
            }

            $slug = $base;
            $i = 2;

            while (static::where('slug', $slug)->where('id', '!=', $model->id)->exists()) {
                $slug = $base.'-'.$i;
                $i++;
            }

            $model->slug = $slug;
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'category_id');
    }

    public function activeItems(): HasMany
    {
        return $this->items()->where('is_available', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function itemsCount(): int
    {
        return $this->items()->count();
    }
}
