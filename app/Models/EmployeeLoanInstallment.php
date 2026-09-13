<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmployeeLoanInstallment extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'employee_loan_id',
        'employee_id',
        'installment_number',
        'due_date',
        'amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'payroll_run_id',
        'payroll_run_item_id',
        'deducted_at',
        'paid_at',
        'notes',
        'metadata',
    ];

    protected $appends = [
        'status_label',
    ];

    protected function casts(): array
    {
        return [
            'installment_number' => 'integer',
            'due_date' => 'date',

            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',

            'payroll_run_id' => 'integer',
            'payroll_run_item_id' => 'integer',

            'deducted_at' => 'date',
            'paid_at' => 'datetime',

            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(
            function (
                EmployeeLoanInstallment $installment
            ) {
                if (!$installment->uuid) {
                    $installment->uuid =
                        (string) Str::uuid();
                }

                if ($installment->paid_amount === null) {
                    $installment->paid_amount = 0;
                }

                if (
                    $installment->remaining_amount === null
                ) {
                    $installment->remaining_amount =
                        $installment->amount;
                }
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | العلاقات
    |--------------------------------------------------------------------------
    */

    public function loan(): BelongsTo
    {
        return $this->belongsTo(
            EmployeeLoan::class,
            'employee_loan_id'
        );
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(
            PayrollRun::class
        );
    }

    public function payrollRunItem(): BelongsTo
    {
        return $this->belongsTo(
            PayrollRunItem::class
        );
    }
    
    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending(
        Builder $query
    ): Builder {
        return $query->whereIn(
            $this->qualifyColumn('status'),
            ['pending', 'partially_paid', 'postponed']
        );
    }

    public function scopeDueOnOrBefore(
        Builder $query,
        mixed $date
    ): Builder {
        return $query->whereDate(
            $this->qualifyColumn('due_date'),
            '<=',
            $date
        );
    }

    public function scopeForPayrollRun(
        Builder $query,
        int $payrollRunId
    ): Builder {
        return $query->where(
            $this->qualifyColumn('payroll_run_id'),
            $payrollRunId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Labels
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'بانتظار الخصم',
            'partially_paid' => 'مسدد جزئيًا',
            'deducted' => 'تم الخصم',
            'paid' => 'مسدد',
            'postponed' => 'مؤجل',
            'cancelled' => 'ملغى',
            default => 'غير محدد',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return in_array(
            $this->status,
            ['pending', 'partially_paid', 'postponed'],
            true
        );
    }

    public function isPaid(): bool
    {
        return in_array(
            $this->status,
            ['deducted', 'paid'],
            true
        );
    }

    public function registerPayment(
        float $amount
    ): self {
        $amount = round($amount, 2);

        $newPaidAmount = min(
            (float) $this->amount,
            round(
                (float) $this->paid_amount + $amount,
                2
            )
        );

        $remainingAmount = max(
            0,
            round(
                (float) $this->amount - $newPaidAmount,
                2
            )
        );

        $this->forceFill([
            'paid_amount' => $newPaidAmount,
            'remaining_amount' => $remainingAmount,
            'status' =>
                $remainingAmount <= 0
                    ? 'paid'
                    : 'partially_paid',
            'paid_at' =>
                $remainingAmount <= 0
                    ? now()
                    : null,
        ])->save();

        $this->loan->refreshTotals();

        return $this->refresh();
    }
}