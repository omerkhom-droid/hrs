<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LeaveBalanceTransaction extends Model
{
    use HasFactory;
    use BelongsToTenant;

    public const TYPE_OPENING = 'opening';
    public const TYPE_ACCRUAL = 'accrual';
    public const TYPE_CARRY_FORWARD = 'carry_forward';
    public const TYPE_USAGE = 'usage';
    public const TYPE_REVERSAL = 'reversal';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_EXPIRY = 'expiry';

    protected $fillable = [
        'tenant_id',
        'leave_balance_id',
        'leave_request_id',
        'type',
        'amount',
        'balance_after',
        'effective_date',
        'notes',
        'created_by',
        'metadata',
    ];

    protected $appends = [
        'type_label',
        'type_color',
        'direction',
    ];

    protected static function booted(): void
    {
        static::creating(
            function (
                LeaveBalanceTransaction $transaction
            ) {
                if (!$transaction->uuid) {
                    $transaction->uuid =
                        (string) Str::uuid();
                }
            }
        );
    }

    protected function casts(): array
    {
        return [
            'amount' =>
                'decimal:2',

            'balance_after' =>
                'decimal:2',

            'effective_date' =>
                'date',

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

    public function leaveBalance(): BelongsTo
    {
        return $this->belongsTo(
            LeaveBalance::class
        );
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(
            LeaveRequest::class
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForBalance(
        Builder $query,
        int $leaveBalanceId
    ): Builder {
        return $query->where(
            $this->qualifyColumn('leave_balance_id'),
            $leaveBalanceId
        );
    }

    public function scopeForRequest(
        Builder $query,
        int $leaveRequestId
    ): Builder {
        return $query->where(
            $this->qualifyColumn('leave_request_id'),
            $leaveRequestId
        );
    }

    public function scopeOfType(
        Builder $query,
        ?string $type
    ): Builder {
        if (
            !$type ||
            !in_array(
                $type,
                self::types(),
                true
            )
        ) {
            return $query;
        }

        return $query->where(
            $this->qualifyColumn('type'),
            $type
        );
    }

    public function scopeBetweenDates(
        Builder $query,
        string $startDate,
        string $endDate
    ): Builder {
        return $query->whereBetween(
            $this->qualifyColumn('effective_date'),
            [
                $startDate,
                $endDate,
            ]
        );
    }

    public function scopeCredits(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('amount'),
            '>',
            0
        );
    }

    public function scopeDebits(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('amount'),
            '<',
            0
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_OPENING =>
                'رصيد افتتاحي',

            self::TYPE_ACCRUAL =>
                'استحقاق',

            self::TYPE_CARRY_FORWARD =>
                'رصيد مرحّل',

            self::TYPE_USAGE =>
                'استخدام رصيد',

            self::TYPE_REVERSAL =>
                'عكس حركة',

            self::TYPE_ADJUSTMENT =>
                'تسوية يدوية',

            self::TYPE_EXPIRY =>
                'انتهاء رصيد',

            default =>
                'غير محدد',
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_OPENING,
            self::TYPE_ACCRUAL,
            self::TYPE_CARRY_FORWARD,
            self::TYPE_REVERSAL =>
                'success',

            self::TYPE_USAGE,
            self::TYPE_EXPIRY =>
                'danger',

            self::TYPE_ADJUSTMENT =>
                'warning',

            default =>
                'secondary',
        };
    }

    public function getDirectionAttribute(): string
    {
        if ((float) $this->amount > 0) {
            return 'credit';
        }

        if ((float) $this->amount < 0) {
            return 'debit';
        }

        return 'neutral';
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isCredit(): bool
    {
        return
            (float) $this->amount > 0;
    }

    public function isDebit(): bool
    {
        return
            (float) $this->amount < 0;
    }

    public function isAdjustment(): bool
    {
        return
            $this->type ===
            self::TYPE_ADJUSTMENT;
    }

    public function formattedAmount(): string
    {
        $amount =
            (float) $this->amount;

        $prefix =
            $amount > 0
                ? '+'
                : '';

        return $prefix . number_format(
            $amount,
            2,
            '.',
            ''
        );
    }

    public static function types(): array
    {
        return [
            self::TYPE_OPENING,
            self::TYPE_ACCRUAL,
            self::TYPE_CARRY_FORWARD,
            self::TYPE_USAGE,
            self::TYPE_REVERSAL,
            self::TYPE_ADJUSTMENT,
            self::TYPE_EXPIRY,
        ];
    }
}