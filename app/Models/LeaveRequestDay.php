<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequestDay extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'leave_request_id',
        'leave_date',
        'amount',
        'is_working_day',
        'is_paid',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'leave_date' =>
                'date',

            'amount' =>
                'decimal:2',

            'is_working_day' =>
                'boolean',

            'is_paid' =>
                'boolean',

            'metadata' =>
                'array',

            'created_at' =>
                'datetime',

            'updated_at' =>
                'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(
            LeaveRequest::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeWorkingDays(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('is_working_day'),
            true
        );
    }

    public function scopeNonWorkingDays(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('is_working_day'),
            false
        );
    }

    public function scopePaid(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('is_paid'),
            true
        );
    }

    public function scopeUnpaid(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('is_paid'),
            false
        );
    }

    public function scopeBetweenDates(
        Builder $query,
        string $startDate,
        string $endDate
    ): Builder {
        return $query->whereBetween(
            $this->qualifyColumn('leave_date'),
            [
                $startDate,
                $endDate,
            ]
        );
    }

    public function scopeForDate(
        Builder $query,
        string $date
    ): Builder {
        return $query->whereDate(
            $this->qualifyColumn('leave_date'),
            $date
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isFullDay(): bool
    {
        return
            (float) $this->amount ===
            1.0;
    }

    public function isHalfDay(): bool
    {
        return
            (float) $this->amount ===
            0.5;
    }

    public function isNonWorkingDay(): bool
    {
        return !$this->is_working_day;
    }

    public function getAmountLabelAttribute(): string
    {
        $amount =
            (float) $this->amount;

        if ($amount === 1.0) {
            return 'يوم كامل';
        }

        if ($amount === 0.5) {
            return 'نصف يوم';
        }

        return number_format(
            $amount,
            2,
            '.',
            ''
        ) . ' يوم';
    }
}