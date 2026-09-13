<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PayrollRun extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;


    /*
    |--------------------------------------------------------------------------
    | أنواع المسيرات
    |--------------------------------------------------------------------------
    */

    public const TYPE_REGULAR = 'regular';
    public const TYPE_OFF_CYCLE = 'off_cycle';
    public const TYPE_FINAL_SETTLEMENT =
        'final_settlement';


    /*
    |--------------------------------------------------------------------------
    | حالات المسير
    |--------------------------------------------------------------------------
    */

    public const STATUS_DRAFT = 'draft';
    public const STATUS_CALCULATING = 'calculating';
    public const STATUS_CALCULATED = 'calculated';
    public const STATUS_REVIEW = 'review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';


    protected $fillable = [
        'uuid',
        'tenant_id',
        'payroll_period_id',
        'run_number',
        'type',
        'status',
        'currency_code',
        'employee_count',
        'total_basic_salary',
        'total_earnings',
        'total_deductions',
        'total_net_salary',
        'calculated_at',
        'calculated_by',
        'approved_at',
        'approved_by',
        'paid_at',
        'paid_by',
        'created_by',
        'notes',
        'metadata',
    ];


    protected function casts(): array
    {
        return [
            'employee_count' =>
                'integer',

            'total_basic_salary' =>
                'decimal:2',

            'total_earnings' =>
                'decimal:2',

            'total_deductions' =>
                'decimal:2',

            'total_net_salary' =>
                'decimal:2',

            'calculated_at' =>
                'datetime',

            'approved_at' =>
                'datetime',

            'paid_at' =>
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
            PayrollRun $run
        ) {
            if (!$run->uuid) {
                $run->uuid =
                    (string) Str::uuid();
            }

            if (!$run->type) {
                $run->type =
                    self::TYPE_REGULAR;
            }

            if (!$run->status) {
                $run->status =
                    self::STATUS_DRAFT;
            }

            if (!$run->currency_code) {
                $run->currency_code =
                    $run->tenant?->currency_code
                    ?? 'SAR';
            }

            if ($run->employee_count === null) {
                $run->employee_count = 0;
            }

            if (
                $run->total_basic_salary ===
                null
            ) {
                $run->total_basic_salary = 0;
            }

            if ($run->total_earnings === null) {
                $run->total_earnings = 0;
            }

            if ($run->total_deductions === null) {
                $run->total_deductions = 0;
            }

            if (
                $run->total_net_salary ===
                null
            ) {
                $run->total_net_salary = 0;
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


    public function period(): BelongsTo
    {
        return $this->belongsTo(
            PayrollPeriod::class,
            'payroll_period_id'
        );
    }


    public function items(): HasMany
    {
        return $this->hasMany(
            PayrollRunItem::class,
            'payroll_run_id'
        )->orderBy('id');
    }


    public function calculatedItems(): HasMany
    {
        return $this->items()
            ->whereIn(
                'status',
                [
                    PayrollRunItem::STATUS_CALCULATED,
                    PayrollRunItem::STATUS_APPROVED,
                    PayrollRunItem::STATUS_PAID,
                ]
            );
    }


    public function exceptionItems(): HasMany
    {
        return $this->items()
            ->where(
                'status',
                PayrollRunItem::STATUS_EXCEPTION
            );
    }


    public function pendingItems(): HasMany
    {
        return $this->items()
            ->where(
                'status',
                PayrollRunItem::STATUS_PENDING
            );
    }


    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'calculated_by'
        );
    }


    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }


    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'paid_by'
        );
    }


    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    public function paymentBatches(): HasMany
    {
        return $this->hasMany(
            PayrollPaymentBatch::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

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


    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->whereNotIn(
            $this->qualifyColumn('status'),
            [
                self::STATUS_CANCELLED,
            ]
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


    public function scopePaid(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('status'),
            self::STATUS_PAID
        );
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


    public function isCalculating(): bool
    {
        return $this->status ===
            self::STATUS_CALCULATING;
    }


    public function isCalculated(): bool
    {
        return $this->status ===
            self::STATUS_CALCULATED;
    }


    public function isReview(): bool
    {
        return $this->status ===
            self::STATUS_REVIEW;
    }


    public function isApproved(): bool
    {
        return $this->status ===
            self::STATUS_APPROVED;
    }


    public function isPaid(): bool
    {
        return $this->status ===
            self::STATUS_PAID;
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


    public function canBeCalculated(): bool
    {
        return
            !$this->trashed() &&
            in_array(
                $this->status,
                [
                    self::STATUS_DRAFT,
                    self::STATUS_CALCULATED,
                    self::STATUS_REVIEW,
                ],
                true
            ) &&
            !$this->period?->is_locked;
    }


    public function canBeSentToReview(): bool
    {
        return
            !$this->trashed() &&
            $this->isCalculated() &&
            !$this->hasExceptions();
    }


    public function canBeApproved(): bool
    {
        return
            !$this->trashed() &&
            $this->isReview() &&
            !$this->hasExceptions() &&
            $this->items()->exists();
    }


    public function canBeMarkedPaid(): bool
    {
        return
            !$this->trashed() &&
            $this->isApproved() &&
            $this->items()
                ->where(
                    'status',
                    '!=',
                    PayrollRunItem::STATUS_APPROVED
                )
                ->doesntExist();
    }


    public function canBeCancelled(): bool
    {
        return
            !$this->trashed() &&
            in_array(
                $this->status,
                [
                    self::STATUS_DRAFT,
                    self::STATUS_CALCULATED,
                    self::STATUS_REVIEW,
                ],
                true
            ) &&
            !$this->period?->is_locked;
    }


    public function canBeDeleted(): bool
    {
        return
            !$this->trashed() &&
            $this->isDraft() &&
            !$this->items()->exists();
    }


    public function hasExceptions(): bool
    {
        if (
            array_key_exists(
                'exception_items_count',
                $this->attributes
            )
        ) {
            return
                (int) $this
                    ->exception_items_count > 0;
        }

        return $this->exceptionItems()
            ->exists();
    }


    /*
    |--------------------------------------------------------------------------
    | Labels
    |--------------------------------------------------------------------------
    */

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_REGULAR =>
                'مسير عادي',

            self::TYPE_OFF_CYCLE =>
                'مسير خارج الدورة',

            self::TYPE_FINAL_SETTLEMENT =>
                'تصفية نهائية',

            default =>
                'غير محدد',
        };
    }


    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT =>
                'مسودة',

            self::STATUS_CALCULATING =>
                'جاري الحساب',

            self::STATUS_CALCULATED =>
                'تم الحساب',

            self::STATUS_REVIEW =>
                'قيد المراجعة',

            self::STATUS_APPROVED =>
                'معتمد',

            self::STATUS_PAID =>
                'تم الصرف',

            self::STATUS_CANCELLED =>
                'ملغي',

            default =>
                'غير محدد',
        };
    }


    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT =>
                'secondary',

            self::STATUS_CALCULATING =>
                'info',

            self::STATUS_CALCULATED =>
                'primary',

            self::STATUS_REVIEW =>
                'warning',

            self::STATUS_APPROVED =>
                'success',

            self::STATUS_PAID =>
                'success',

            self::STATUS_CANCELLED =>
                'danger',

            default =>
                'secondary',
        };
    }


    /*
    |--------------------------------------------------------------------------
    | القيم المالية
    |--------------------------------------------------------------------------
    */

    public function getGrossTotalAttribute(): float
    {
        return round(
            (float) $this->total_earnings,
            2
        );
    }


    public function getCalculatedNetTotalAttribute(): float
    {
        return round(
            (float) $this->total_earnings -
            (float) $this->total_deductions,
            2
        );
    }


    public function totalsAreBalanced(): bool
    {
        return abs(
            (float) $this->total_net_salary -
            $this->calculated_net_total
        ) < 0.01;
    }
}