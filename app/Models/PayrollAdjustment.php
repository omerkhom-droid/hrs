<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PayrollAdjustment extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;


    /*
    |--------------------------------------------------------------------------
    | نوع التسوية
    |--------------------------------------------------------------------------
    */

    public const TYPE_EARNING = 'earning';
    public const TYPE_DEDUCTION = 'deduction';


    /*
    |--------------------------------------------------------------------------
    | حالات التسوية
    |--------------------------------------------------------------------------
    */

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';


    protected $fillable = [
        'uuid',
        'tenant_id',
        'employee_id',
        'payroll_period_id',
        'salary_component_id',
        'applied_payroll_item_id',
        'adjustment_number',
        'type',
        'amount',
        'currency_code',
        'effective_date',
        'status',
        'reason',
        'notes',
        'approved_by',
        'approved_at',
        'created_by',
        'metadata',
    ];


    protected function casts(): array
    {
        return [
            'amount' =>
                'decimal:2',

            'effective_date' =>
                'date',

            'approved_at' =>
                'datetime',

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


    protected static function booted(): void
    {
        static::creating(function (
            PayrollAdjustment $adjustment
        ) {
            if (!$adjustment->uuid) {
                $adjustment->uuid =
                    (string) Str::uuid();
            }

            if (!$adjustment->status) {
                $adjustment->status =
                    self::STATUS_DRAFT;
            }

            if (!$adjustment->currency_code) {
                $adjustment->currency_code =
                    $adjustment->tenant
                        ?->currency_code
                    ?? 'SAR';
            }
        });
    }


    /*
    |--------------------------------------------------------------------------
    | العلاقات
    |--------------------------------------------------------------------------
    */

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }


    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class
        );
    }


    public function period(): BelongsTo
    {
        return $this->belongsTo(
            PayrollPeriod::class,
            'payroll_period_id'
        );
    }


    public function salaryComponent(): BelongsTo
    {
        return $this->belongsTo(
            SalaryComponent::class,
            'salary_component_id'
        );
    }
    
    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(
            PayrollPeriod::class,
            'payroll_period_id'
        );
    }

    public function appliedPayrollItem(): BelongsTo
    {
        return $this->belongsTo(
            PayrollRunItem::class,
            'applied_payroll_item_id'
        );
    }


    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }


    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Scopes
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


    public function scopeForPeriod(
        Builder $query,
        int $periodId
    ): Builder {
        return $query->where(
            $this->qualifyColumn(
                'payroll_period_id'
            ),
            $periodId
        );
    }


    public function scopeOfType(
        Builder $query,
        ?string $type
    ): Builder {
        if (!$type) {
            return $query;
        }

        return $query->where(
            $this->qualifyColumn('type'),
            $type
        );
    }


    public function scopeWithStatus(
        Builder $query,
        ?string $status
    ): Builder {
        if (!$status) {
            return $query;
        }

        return $query->where(
            $this->qualifyColumn('status'),
            $status
        );
    }


    public function scopePending(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('status'),
            self::STATUS_PENDING
        );
    }


    public function scopeApproved(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('status'),
            self::STATUS_APPROVED
        );
    }


    public function scopeUnapplied(
        Builder $query
    ): Builder {
        return $query
            ->where(
                $this->qualifyColumn('status'),
                self::STATUS_APPROVED
            )
            ->whereNull(
                $this->qualifyColumn(
                    'applied_payroll_item_id'
                )
            );
    }


    public function scopeEffectiveBetween(
        Builder $query,
        string $startDate,
        string $endDate
    ): Builder {
        return $query->whereBetween(
            $this->qualifyColumn(
                'effective_date'
            ),
            [
                $startDate,
                $endDate,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | فحص النوع
    |--------------------------------------------------------------------------
    */

    public function isEarning(): bool
    {
        return $this->type ===
            self::TYPE_EARNING;
    }


    public function isDeduction(): bool
    {
        return $this->type ===
            self::TYPE_DEDUCTION;
    }


    /*
    |--------------------------------------------------------------------------
    | فحص الحالة
    |--------------------------------------------------------------------------
    */

    public function isDraft(): bool
    {
        return $this->status ===
            self::STATUS_DRAFT;
    }


    public function isPending(): bool
    {
        return $this->status ===
            self::STATUS_PENDING;
    }


    public function isApproved(): bool
    {
        return $this->status ===
            self::STATUS_APPROVED;
    }


    public function isApplied(): bool
    {
        return $this->status ===
            self::STATUS_APPLIED;
    }


    public function isRejected(): bool
    {
        return $this->status ===
            self::STATUS_REJECTED;
    }


    public function isCancelled(): bool
    {
        return $this->status ===
            self::STATUS_CANCELLED;
    }


    /*
    |--------------------------------------------------------------------------
    | قواعد العمليات
    |--------------------------------------------------------------------------
    */

    public function canBeEdited(): bool
    {
        return
            !$this->trashed() &&
            $this->isDraft();
    }


    public function canBeSubmitted(): bool
    {
        return
            !$this->trashed() &&
            $this->isDraft() &&
            (float) $this->amount > 0;
    }


    public function canBeApproved(): bool
    {
        return
            !$this->trashed() &&
            $this->isPending();
    }


    public function canBeRejected(): bool
    {
        return
            !$this->trashed() &&
            $this->isPending();
    }


    public function canBeApplied(): bool
    {
        return
            !$this->trashed() &&
            $this->isApproved() &&
            $this->applied_payroll_item_id ===
                null;
    }


    public function canBeCancelled(): bool
    {
        return
            !$this->trashed() &&
            in_array(
                $this->status,
                [
                    self::STATUS_DRAFT,
                    self::STATUS_PENDING,
                    self::STATUS_APPROVED,
                ],
                true
            ) &&
            $this->applied_payroll_item_id ===
                null;
    }


    public function canBeDeleted(): bool
    {
        return
            !$this->trashed() &&
            $this->isDraft();
    }


    /*
    |--------------------------------------------------------------------------
    | القيم المالية
    |--------------------------------------------------------------------------
    */

    public function getSignedAmountAttribute(): float
    {
        $amount = round(
            (float) $this->amount,
            2
        );

        return $this->isDeduction()
            ? -$amount
            : $amount;
    }


    /*
    |--------------------------------------------------------------------------
    | Labels
    |--------------------------------------------------------------------------
    */

    public function getTypeLabelAttribute(): string
    {
        return $this->isEarning()
            ? 'استحقاق'
            : 'استقطاع';
    }


    public function getTypeColorAttribute(): string
    {
        return $this->isEarning()
            ? 'success'
            : 'danger';
    }


    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT =>
                'مسودة',

            self::STATUS_PENDING =>
                'بانتظار الاعتماد',

            self::STATUS_APPROVED =>
                'معتمدة',

            self::STATUS_APPLIED =>
                'تم تطبيقها',

            self::STATUS_REJECTED =>
                'مرفوضة',

            self::STATUS_CANCELLED =>
                'ملغاة',

            default =>
                'غير محددة',
        };
    }


    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT =>
                'secondary',

            self::STATUS_PENDING =>
                'warning',

            self::STATUS_APPROVED =>
                'success',

            self::STATUS_APPLIED =>
                'primary',

            self::STATUS_REJECTED =>
                'danger',

            self::STATUS_CANCELLED =>
                'dark',

            default =>
                'secondary',
        };
    }
}