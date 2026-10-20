<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'birth_date',
        'notes',
        'total_visits',
        'total_spent',
        'last_visit',
        'is_vip',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'last_visit' => 'date',
            'total_visits' => 'integer',
            'total_spent' => 'float',
            'is_vip' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'client_email', 'email');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        return $query->where('name', 'like', '%'.trim($term).'%');
    }

    public function scopeVip(Builder $query, ?string $filter): Builder
    {
        return $filter === '1' ? $query->where('is_vip', true) : $query;
    }

    /**
     * Actualiza el tracking de visitas y gasto acumulado del cliente.
     */
    public function registerVisit(float $amount): void
    {
        $this->increment('total_visits');
        $this->total_spent = $this->total_spent + $amount;
        $this->last_visit = now()->toDateString();
        $this->save();
    }
}
