<?php

namespace App\Models;

use App\Enums\TableLocation;
use App\Enums\TableStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'cleaning_started_at',
        'ready_confirmed_by',
        'ready_confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'status' => TableStatus::class,
            'is_active' => 'boolean',
            'cleaning_started_at' => 'datetime',
            'ready_confirmed_at' => 'datetime',
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

    public function readyConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ready_confirmed_by');
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

    public function scopeCleaning(Builder $query): Builder
    {
        return $query->where('status', TableStatus::Cleaning->value);
    }

    public function scopeDelayedCleaning(Builder $query, ?int $minutes = null): Builder
    {
        $min = $minutes ?? (int) config('restauranager.reservation_turnover_buffer_minutes', 15);

        return $query->where('status', TableStatus::Cleaning->value)
            ->whereNotNull('cleaning_started_at')
            ->where('cleaning_started_at', '<=', now()->subMinutes($min));
    }

    public function isDelayedCleaning(?int $minutes = null): bool
    {
        if ($this->status !== TableStatus::Cleaning || ! $this->cleaning_started_at) {
            return false;
        }

        $min = $minutes ?? (int) config('restauranager.reservation_turnover_buffer_minutes', 15);

        return $this->cleaning_started_at->diffInMinutes(now()) >= $min;
    }

    /**
     * Una mesa en occupied, cleaning o maintenance no puede asignarse a una nueva orden.
     */
    public function isAssignableToOrder(): bool
    {
        return $this->is_active && ! in_array($this->status, [
            TableStatus::Occupied,
            TableStatus::Cleaning,
            TableStatus::Maintenance,
        ], true);
    }

    public function scopeAssignableToOrder(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNotIn('status', [
            TableStatus::Occupied->value,
            TableStatus::Cleaning->value,
            TableStatus::Maintenance->value,
        ]);
    }

    public function setStatus(TableStatus $status): void
    {
        if ($this->status !== $status) {
            $data = ['status' => $status->value];

            if ($status === TableStatus::Cleaning) {
                $data['cleaning_started_at'] = now();
            }

            $this->forceFill($data)->save();
        }
    }

    /**
     * Marca una mesa en limpieza como lista y disponible.
     */
    public function markAsReady(?int $userId = null): void
    {
        $this->forceFill([
            'status' => TableStatus::Available->value,
            'ready_confirmed_by' => $userId,
            'ready_confirmed_at' => now(),
        ])->save();
    }
}
