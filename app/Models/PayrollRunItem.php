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

class PayrollRunItem extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;


    public const STATUS_PENDING = 'pending';
    public const STATUS_CALCULATED = 'calculated';
    public const STATUS_EXCEPTION = 'exception';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';


    protected $fillable = [
        'uuid',
        'tenant_id',
        'payroll_run_id',
        'employee_id',
        'salary_structure_id',
        'currency_code',
        'basic_salary',
        'gross_salary',
        'total_earnings',
        'total_deductions',
        'net_salary',
        'scheduled_work_days',
        'actual_work_days',
        'absent_days',
        'paid_leave_days',
        'unpaid_leave_days',
        'overtime_minutes',
        'status',
        'calculation_snapshot',
        'errors',
        'metadata',
    ];


    protected function casts(): array
    {
        return [
            'basic_salary' =>
                'decimal:2',

            'gross_salary' =>
                'decimal:2',

            'total_earnings' =>
                'decimal:2',

            'total_deductions' =>
                'decimal:2',

            'net_salary' =>
                'decimal:2',

            'scheduled_work_days' =>
                'decimal:2',

            'actual_work_days' =>
                'decimal:2',

            'absent_days' =>
                'decimal:2',

            'paid_leave_days' =>
                'decimal:2',

            'unpaid_leave_days' =>
                'decimal:2',

            'overtime_minutes' =>
                'integer',

            'calculation_snapshot' =>
                'array',

            'errors' =>
                'array',

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
            PayrollRunItem $item
        ) {
            if (!$item->uuid) {
                $item->uuid =
                    (string) Str::uuid();
            }

            if (!$item->status) {
                $item->status =
                    self::STATUS_PENDING;
            }

            if (!$item->currency_code) {
                $item->currency_code =
                    $item->payrollRun
                        ?->currency_code
                    ?? $item->tenant
                        ?->currency_code
                    ?? 'SAR';
            }

            $defaults = [
                'basic_salary',
                'gross_salary',
                'total_earnings',
                'total_deductions',
                'net_salary',
                'scheduled_work_days',
                'actual_work_days',
                'absent_days',
                'paid_leave_days',
                'unpaid_leave_days',
                'overtime_minutes',
            ];

            foreach ($defaults as $field) {
                if ($item->{$field} === null) {
                    $item->{$field} = 0;
                }
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


    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(
            PayrollRun::class,
            'payroll_run_id'
        );
    }


    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class
        );
    }


    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(
            EmployeeSalaryStructure::class,
            'salary_structure_id'
        );
    }


    public function components(): HasMany
    {
        return $this->hasMany(
            PayrollRunItemComponent::class,
            'payroll_item_id'
        )->orderBy('id');
    }


    public function earningComponents(): HasMany
    {
        return $this->components()
            ->where(
                'type',
                PayrollRunItemComponent::TYPE_EARNING
            );
    }


    public function deductionComponents(): HasMany
    {
        return $this->components()
            ->where(
                'type',
                PayrollRunItemComponent::TYPE_DEDUCTION
            );
    }


    public function appliedAdjustments(): HasMany
    {
        return $this->hasMany(
            PayrollAdjustment::class,
            'applied_payroll_item_id'
        );
    }

    public function paymentBatchItems(): HasMany
    {
        return $this->hasMany(
            PayrollPaymentBatchItem::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForRun(
        Builder $query,
        int $runId
    ): Builder {
        return $query->where(
            $this->qualifyColumn(
                'payroll_run_id'
            ),
            $runId
        );
    }


    public function scopeForEmployee(
        Builder $query,
        int $employeeId
    ): Builder {
        return $query->where(
            $this->qualifyColumn(
                'employee_id'
            ),
            $employeeId
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


    public function scopeCalculated(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('status'),
            self::STATUS_CALCULATED
        );
    }


    public function scopeExceptions(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('status'),
            self::STATUS_EXCEPTION
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


    /*
    |--------------------------------------------------------------------------
    | فحص الحالة
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status ===
            self::STATUS_PENDING;
    }


    public function isCalculated(): bool
    {
        return $this->status ===
            self::STATUS_CALCULATED;
    }


    public function isException(): bool
    {
        return $this->status ===
            self::STATUS_EXCEPTION;
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


    /*
    |--------------------------------------------------------------------------
    | قواعد العمليات
    |--------------------------------------------------------------------------
    */

    public function canBeRecalculated(): bool
    {
        return
            !$this->trashed() &&
            in_array(
                $this->status,
                [
                    self::STATUS_PENDING,
                    self::STATUS_CALCULATED,
                    self::STATUS_EXCEPTION,
                ],
                true
            ) &&
            $this->payrollRun
                ?->canBeCalculated();
    }


    public function canBeApproved(): bool
    {
        return
            !$this->trashed() &&
            $this->isCalculated() &&
            !$this->hasErrors();
    }


    public function canBeMarkedPaid(): bool
    {
        return
            !$this->trashed() &&
            $this->isApproved();
    }


    public function hasErrors(): bool
    {
        return !empty(
            $this->errors ?? []
        );
    }


    /*
    |--------------------------------------------------------------------------
    | القيم المالية
    |--------------------------------------------------------------------------
    */

    public function getCalculatedNetSalaryAttribute(): float
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
            (float) $this->net_salary -
            $this->calculated_net_salary
        ) < 0.01;
    }


    public function getTotalAllowancesAttribute(): float
    {
        return round(
            max(
                0,
                (float) $this->total_earnings -
                (float) $this->basic_salary
            ),
            2
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ملخص الحضور
    |--------------------------------------------------------------------------
    */

    public function getAttendanceSummaryAttribute(): array
    {
        return [
            'scheduled_work_days' =>
                (float) $this
                    ->scheduled_work_days,

            'actual_work_days' =>
                (float) $this
                    ->actual_work_days,

            'absent_days' =>
                (float) $this
                    ->absent_days,

            'paid_leave_days' =>
                (float) $this
                    ->paid_leave_days,

            'unpaid_leave_days' =>
                (float) $this
                    ->unpaid_leave_days,

            'overtime_minutes' =>
                (int) $this
                    ->overtime_minutes,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Labels
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING =>
                'بانتظار الحساب',

            self::STATUS_CALCULATED =>
                'تم الحساب',

            self::STATUS_EXCEPTION =>
                'يحتاج مراجعة',

            self::STATUS_APPROVED =>
                'معتمد',

            self::STATUS_PAID =>
                'تم الصرف',

            default =>
                'غير محدد',
        };
    }


    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING =>
                'secondary',

            self::STATUS_CALCULATED =>
                'primary',

            self::STATUS_EXCEPTION =>
                'danger',

            self::STATUS_APPROVED =>
                'success',

            self::STATUS_PAID =>
                'success',

            default =>
                'secondary',
        };
    }
}