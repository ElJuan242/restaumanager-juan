<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use App\Enums\TableStatus;
use Carbon\Carbon;
use Carbon\CarbonInterface;
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
        'duration_minutes',
        'reservation_end',
        'people_count',
        'restaurant_table_id',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'reservation_date' => 'datetime',
            'reservation_end' => 'datetime',
            'duration_minutes' => 'integer',
            'people_count' => 'integer',
            'status' => ReservationStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (! $model->duration_minutes) {
                $model->duration_minutes = (int) config('restauranager.reservation_duration_minutes', 120);
            }

            if ($model->reservation_date) {
                $inicio = $model->reservation_date instanceof CarbonInterface
                    ? $model->reservation_date->copy()
                    : Carbon::parse($model->reservation_date);

                $model->reservation_end = $inicio->addMinutes((int) $model->duration_minutes);
            }
        });
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
            'today' => $query->whereDate('reservation_date', today('America/Bogota')),
            'upcoming' => $query->upcoming(),
            'past' => $query->where('reservation_date', '<', today('America/Bogota')),
            default => $query,
        };
    }

    /**
     * Reservas próximas: pendientes o confirmadas con inicio dentro de las
     * siguientes 12 horas, tomando como referencia America/Bogota.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        $inicio = now('America/Bogota');
        $fin = $inicio->copy()->addHours(12);

        return $query
            ->whereIn('status', [
                ReservationStatus::Pending->value,
                ReservationStatus::Confirmed->value,
            ])
            ->whereBetween('reservation_date', [$inicio, $fin]);
    }

    /**
     * Busca si existe una reserva en conflicto en la misma mesa (RN-04 / RF-45).
     *
     * Condición con margen de rotación (15 min):
     * A.inicio < B.fin + 15 min Y A.fin + 15 min > B.inicio
     * Equivalente a:
     * B.fin > A.inicio - 15 min Y B.inicio < A.fin + 15 min
     */
    public function findConflictingReservation(): ?self
    {
        if (! $this->restaurant_table_id || ! $this->reservation_date) {
            return null;
        }

        // Cancelled y Completed liberan la ventana y no generan conflicto
        if (in_array($this->status, [ReservationStatus::Cancelled, ReservationStatus::Completed], true)) {
            return null;
        }

        $bufferMinutes = (int) config('restauranager.reservation_turnover_buffer_minutes', 15);

        $aInicio = $this->reservation_date instanceof CarbonInterface
            ? $this->reservation_date->copy()
            : Carbon::parse($this->reservation_date);

        $duracion = (int) ($this->duration_minutes ?: config('restauranager.reservation_duration_minutes', 120));

        $aFin = $this->reservation_end instanceof CarbonInterface
            ? $this->reservation_end->copy()
            : $aInicio->copy()->addMinutes($duracion);

        $umbralInicio = $aInicio->copy()->subMinutes($bufferMinutes);
        $umbralFin = $aFin->copy()->addMinutes($bufferMinutes);

        return self::query()
            ->where('restaurant_table_id', $this->restaurant_table_id)
            ->when($this->exists, fn ($q) => $q->where('id', '!=', $this->id))
            ->whereIn('status', [ReservationStatus::Pending->value, ReservationStatus::Confirmed->value])
            ->where('reservation_end', '>', $umbralInicio)
            ->where('reservation_date', '<', $umbralFin)
            ->orderBy('reservation_date')
            ->first();
    }

    /**
     * Verifica capacidad, estado de mantenimiento y ventana de disponibilidad sin conflicto.
     *
     * Devuelve los errores indexados por el campo de validación correspondiente.
     *
     * @return array<string, string>
     */
    public function validateTableErrors(): array
    {
        if (! $this->restaurant_table_id) {
            return [];
        }

        $table = RestaurantTable::find($this->restaurant_table_id);

        if (! $table || ! $table->is_active) {
            return ['restaurant_table_id' => 'La mesa seleccionada no existe o está inactiva.'];
        }

        // Salvo maintenance, la disponibilidad para reservas futuras usa la ventana, no el estado actual
        if ($table->status === TableStatus::Maintenance) {
            return ['restaurant_table_id' => sprintf(
                'La mesa %s no está disponible para reservas porque se encuentra en mantenimiento.',
                $table->table_number
            )];
        }

        if ($table->capacity < $this->people_count) {
            return ['restaurant_table_id' => sprintf(
                'La mesa %s tiene capacidad para %d personas y la reserva es para %d.',
                $table->table_number,
                $table->capacity,
                $this->people_count
            )];
        }

        $conflicto = $this->findConflictingReservation();

        if ($conflicto) {
            return ['reservation_date' => sprintf(
                'La mesa %s ya tiene una reserva en conflicto de %s a %s para el cliente %s.',
                $table->table_number,
                time_es($conflicto->reservation_date),
                time_es($conflicto->reservation_end),
                $conflicto->client_name
            )];
        }

        return [];
    }

    /**
     * Primer error de disponibilidad de la mesa (compatibilidad con llamadas previas).
     */
    public function validateTable(): ?string
    {
        $errores = $this->validateTableErrors();

        return $errores === [] ? null : (string) reset($errores);
    }
}
