<?php

namespace App\Models;

use App\Enums\TableLocation;
use App\Enums\TableStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_number',
        'capacity',
        'location',
        'status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'status' => TableStatus::class,
            'is_active' => 'boolean',
        ];
    }

    public function locationEnum(): TableLocation
    {
        return TableLocation::tryFrom((string) $this->location) ?? TableLocation::SalonPrincipal;
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', TableStatus::Available->value);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        return $query->where('table_number', 'like', '%'.trim($term).'%');
    }

    public function scopeLocation(Builder $query, ?string $location): Builder
    {
        if ($location === null || $location === '') {
            return $query;
        }

        return $query->where('location', $location);
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if ($status === null || $status === '') {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function scopeMinCapacity(Builder $query, ?int $capacity): Builder
    {
        if (! $capacity) {
            return $query;
        }

        return $query->where('capacity', '>=', $capacity);
    }

    public function setStatus(TableStatus $status): void
    {
        if ($this->status !== $status) {
            $this->forceFill(['status' => $status->value])->save();
        }
    }
}
