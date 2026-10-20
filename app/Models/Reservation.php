<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_name',
        'client_phone',
        'client_email',
        'reservation_date',
        'people_count',
        'restaurant_table_id',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'reservation_date' => 'datetime',
            'people_count' => 'integer',
            'status' => ReservationStatus::class,
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, [ReservationStatus::Pending, ReservationStatus::Confirmed], true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        return $query->where('client_name', 'like', '%'.trim($term).'%');
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if ($status === null || $status === '') {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function scopeDateFilter(Builder $query, ?string $filter): Builder
    {
        return match ($filter) {
            'today' => $query->whereDate('reservation_date', today()),
            'upcoming' => $query->where('reservation_date', '>=', now()->startOfDay()),
            'past' => $query->where('reservation_date', '<', now()->startOfDay()),
            default => $query,
        };
    }

    /**
     * Verifica que la mesa elegida exista, esté activa y tenga capacidad suficiente.
     */
    public function validateTable(): ?string
    {
        if (! $this->restaurant_table_id) {
            return null;
        }

        $table = RestaurantTable::find($this->restaurant_table_id);

        if (! $table || ! $table->is_active) {
            return 'La mesa seleccionada no existe o está inactiva.';
        }

        if ($table->capacity < $this->people_count) {
            return sprintf(
                'La mesa %s tiene capacidad para %d personas y la reserva es para %d.',
                $table->table_number,
                $table->capacity,
                $this->people_count
            );
        }

        return null;
    }
}
