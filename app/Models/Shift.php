<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'start_time',
        'end_time',
        'actual_start',
        'actual_end',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function duration(): string
    {
        return shift_duration($this->start_time, $this->end_time);
    }

    public function actualDuration(): string
    {
        if (! $this->actual_start || ! $this->actual_end) {
            return '—';
        }

        return shift_duration($this->actual_start, $this->actual_end);
    }

    public function scopeDateFilter(Builder $query, ?string $date): Builder
    {
        return $query->whereDate('date', $date ?: today());
    }

    public function scopeEmployee(Builder $query, ?int $employeeId): Builder
    {
        if (! $employeeId) {
            return $query;
        }

        return $query->where('employee_id', $employeeId);
    }
}
