<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LeaveType extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'name_en',
        'unit',
        'payment_type',
        'paid_percentage',
        'default_entitlement',
        'accrual_method',
        'requires_balance',
        'allow_negative_balance',
        'allow_during_probation',
        'allow_half_day',
        'requires_attachment',
        'minimum_notice_days',
        'maximum_consecutive_days',
        'allow_carry_forward',
        'maximum_carry_forward',
        'gender',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected $appends = [
        'display_name',
        'unit_label',
        'payment_type_label',
        'accrual_method_label',
    ];

    protected static function booted(): void
    {
        static::creating(function (LeaveType $leaveType) {
            if (!$leaveType->uuid) {
                $leaveType->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'paid_percentage' =>
                'decimal:2',

            'default_entitlement' =>
                'decimal:2',

            'requires_balance' =>
                'boolean',

            'allow_negative_balance' =>
                'boolean',

            'allow_during_probation' =>
                'boolean',

            'allow_half_day' =>
                'boolean',

            'requires_attachment' =>
                'boolean',

            'minimum_notice_days' =>
                'integer',

            'maximum_consecutive_days' =>
                'integer',

            'allow_carry_forward' =>
                'boolean',

            'maximum_carry_forward' =>
                'decimal:2',

            'sort_order' =>
                'integer',

            'is_active' =>
                'boolean',

            'metadata' =>
                'array',

            'created_at' =>
                'datetime',

            'updated_at' =>
                'datetime',

            'deleted_at' =>
                'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function balances(): HasMany
    {
        return $this->hasMany(
            LeaveBalance::class
        );
    }

    public function requests(): HasMany
    {
        return $this->hasMany(
            LeaveRequest::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('is_active'),
            true
        );
    }

    public function scopeSearch(
        Builder $query,
        ?string $search
    ): Builder {
        $search = trim(
            (string) $search
        );

        if ($search === '') {
            return $query;
        }

        return $query->where(
            function (Builder $query) use ($search) {
                $query
                    ->where(
                        $this->qualifyColumn('name'),
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        $this->qualifyColumn('name_en'),
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        $this->qualifyColumn('code'),
                        'like',
                        "%{$search}%"
                    );
            }
        );
    }

    public function scopeForGender(
        Builder $query,
        ?string $gender
    ): Builder {
        if (
            !$gender ||
            !in_array(
                $gender,
                ['male', 'female'],
                true
            )
        ) {
            return $query;
        }

        return $query->where(
            function (Builder $query) use ($gender) {
                $query
                    ->where(
                        $this->qualifyColumn('gender'),
                        'all'
                    )
                    ->orWhere(
                        $this->qualifyColumn('gender'),
                        $gender
                    );
            }
        );
    }

    public function scopeOrdered(
        Builder $query
    ): Builder {
        return $query
            ->orderBy(
                $this->qualifyColumn('sort_order')
            )
            ->orderBy(
                $this->qualifyColumn('name')
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getDisplayNameAttribute(): string
    {
        if (
            app()->getLocale() === 'en' &&
            filled($this->name_en)
        ) {
            return $this->name_en;
        }

        return $this->name;
    }

    public function getUnitLabelAttribute(): string
    {
        return match ($this->unit) {
            'day' =>
                'يوم',

            'hour' =>
                'ساعة',

            default =>
                'غير محدد',
        };
    }

    public function getPaymentTypeLabelAttribute(): string
    {
        return match ($this->payment_type) {
            'paid' =>
                'مدفوعة الأجر',

            'unpaid' =>
                'غير مدفوعة الأجر',

            'partially_paid' =>
                'مدفوعة جزئيًا',

            default =>
                'غير محدد',
        };
    }

    public function getAccrualMethodLabelAttribute(): string
    {
        return match ($this->accrual_method) {
            'none' =>
                'بدون استحقاق تراكمي',

            'annual' =>
                'استحقاق سنوي',

            'monthly' =>
                'استحقاق شهري',

            default =>
                'غير محدد',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isAvailableForGender(
        ?string $gender
    ): bool {
        if ($this->gender === 'all') {
            return true;
        }

        return $gender !== null &&
            $this->gender === $gender;
    }

    public function isPaid(): bool
    {
        return $this->payment_type === 'paid';
    }

    public function isUnpaid(): bool
    {
        return $this->payment_type === 'unpaid';
    }

    public function isPartiallyPaid(): bool
    {
        return $this->payment_type === 'partially_paid';
    }

    public function usesBalance(): bool
    {
        return (bool) $this->requires_balance;
    }

    public function supportsHalfDay(): bool
    {
        return
            $this->unit === 'day' &&
            (bool) $this->allow_half_day;
    }

    public function canCarryForward(): bool
    {
        return (bool) $this->allow_carry_forward;
    }
}