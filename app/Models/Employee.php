<?php

namespace App\Models;

use App\Enums\EmployeeRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'role',
        'position',
        'document_id',
        'salary',
        'hired_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'role' => EmployeeRole::class,
            'salary' => 'float',
            'hired_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        return $query->where('name', 'like', '%'.trim($term).'%');
    }

    public function scopeRole(Builder $query, ?string $role): Builder
    {
        if ($role === null || $role === '') {
            return $query;
        }

        return $query->where('role', $role);
    }

    public function scopeActiveFilter(Builder $query, ?string $filter): Builder
    {
        return match ($filter) {
            '1' => $query->where('is_active', true),
            '0' => $query->where('is_active', false),
            default => $query,
        };
    }

    /**
     * Historial de turnos: últimos 10 registrados.
     */
    public function recentShifts(int $limit = 10)
    {
        return $this->shifts()->with('employee')->orderByDesc('date')->orderByDesc('start_time')->take($limit)->get();
    }
}
