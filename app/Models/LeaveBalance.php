<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveBalance extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'leave_type_id',
        'year',
        'opening_balance',
        'accrued_balance',
        'carried_forward',
        'adjustment_balance',
        'used_balance',
        'pending_balance',
        'available_balance',
        'calculated_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'year' =>
                'integer',

            'opening_balance' =>
                'decimal:2',

            'accrued_balance' =>
                'decimal:2',

            'carried_forward' =>
                'decimal:2',

            'adjustment_balance' =>
                'decimal:2',

            'used_balance' =>
                'decimal:2',

            'pending_balance' =>
                'decimal:2',

            'available_balance' =>
                'decimal:2',

            'calculated_at' =>
                'datetime',

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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class
        );
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(
            LeaveType::class
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(
            LeaveBalanceTransaction::class
        )->latest('effective_date')
            ->latest('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForEmployee(
        Builder $query,
        int $employeeId
    ): Builder {
        return $query->where(
            $this->qualifyColumn('employee_id'),
            $employeeId
        );
    }

    public function scopeForLeaveType(
        Builder $query,
        int $leaveTypeId
    ): Builder {
        return $query->where(
            $this->qualifyColumn('leave_type_id'),
            $leaveTypeId
        );
    }

    public function scopeForYear(
        Builder $query,
        int $year
    ): Builder {
        return $query->where(
            $this->qualifyColumn('year'),
            $year
        );
    }

    public function scopeCurrentYear(
        Builder $query
    ): Builder {
        return $query->forYear(
            (int) now()->format('Y')
        );
    }

    public function scopeWithAvailableBalance(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('available_balance'),
            '>',
            0
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Calculations
    |--------------------------------------------------------------------------
    */

    public function entitlementTotal(): float
    {
        return round(
            (float) $this->opening_balance +
            (float) $this->accrued_balance +
            (float) $this->carried_forward +
            (float) $this->adjustment_balance,
            2
        );
    }

    public function consumedTotal(): float
    {
        return round(
            (float) $this->used_balance +
            (float) $this->pending_balance,
            2
        );
    }

    public function calculateAvailableBalance(): float
    {
        return round(
            $this->entitlementTotal() -
            $this->consumedTotal(),
            2
        );
    }

    public function refreshAvailableBalance(): self
    {
        $this->forceFill([
            'available_balance' =>
                $this->calculateAvailableBalance(),

            'calculated_at' =>
                now(),
        ])->save();

        return $this->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Balance Operations
    |--------------------------------------------------------------------------
    */

    public function canCover(
        float $amount
    ): bool {
        if ($amount <= 0) {
            return false;
        }

        if (
            $this->leaveType &&
            $this->leaveType->allow_negative_balance
        ) {
            return true;
        }

        return
            (float) $this->available_balance >=
            $amount;
    }

    public function reserve(
        float $amount
    ): self {
        $amount = round(
            $amount,
            2
        );

        $this->forceFill([
            'pending_balance' =>
                round(
                    (float) $this->pending_balance +
                    $amount,
                    2
                ),
        ]);

        $this->available_balance =
            $this->calculateAvailableBalance();

        $this->calculated_at = now();

        $this->save();

        return $this->refresh();
    }

    public function releaseReservation(
        float $amount
    ): self {
        $amount = round(
            $amount,
            2
        );

        $this->forceFill([
            'pending_balance' =>
                max(
                    0,
                    round(
                        (float) $this->pending_balance -
                        $amount,
                        2
                    )
                ),
        ]);

        $this->available_balance =
            $this->calculateAvailableBalance();

        $this->calculated_at = now();

        $this->save();

        return $this->refresh();
    }

    public function consumeReservedBalance(
        float $amount
    ): self {
        $amount = round(
            $amount,
            2
        );

        $this->forceFill([
            'pending_balance' =>
                max(
                    0,
                    round(
                        (float) $this->pending_balance -
                        $amount,
                        2
                    )
                ),

            'used_balance' =>
                round(
                    (float) $this->used_balance +
                    $amount,
                    2
                ),
        ]);

        $this->available_balance =
            $this->calculateAvailableBalance();

        $this->calculated_at = now();

        $this->save();

        return $this->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isCurrentYear(): bool
    {
        return
            (int) $this->year ===
            (int) now()->format('Y');
    }

    public function hasAvailableBalance(): bool
    {
        return
            (float) $this->available_balance > 0;
    }
}