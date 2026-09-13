<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PayrollPaymentBatchItem extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;


    protected $fillable = [
        'uuid',
        'tenant_id',

        'payroll_payment_batch_id',
        'payroll_run_item_id',
        'employee_id',
        'employee_bank_account_id',

        'employee_number',
        'employee_name',

        'amount',
        'currency_code',
        'payment_method',

        'bank_name',
        'bank_code',
        'bank_branch_code',
        'account_holder_name',

        'account_number',
        'account_number_last_four',

        'iban',
        'iban_hash',
        'iban_last_four',

        'swift_code',

        'status',
        'exception_code',
        'exception_message',

        'bank_transaction_reference',

        'submitted_at',
        'paid_at',
        'failed_at',

        'notes',
        'metadata',
    ];


    protected $hidden = [
        'account_number',
        'iban',
        'iban_hash',
    ];


    protected $appends = [
        'status_label',
        'payment_method_label',
        'masked_account_number',
        'masked_iban',
        'is_ready_for_transfer',
    ];


    protected static function booted(): void
    {
        static::creating(function (
            PayrollPaymentBatchItem $item
        ) {
            if (!$item->uuid) {
                $item->uuid = (string) Str::uuid();
            }
        });
    }


    protected function casts(): array
    {
        return [
            /*
             * تشفير معلومات حساب الموظف.
             */
            'account_number' =>
                'encrypted',

            'iban' =>
                'encrypted',

            'amount' =>
                'decimal:2',

            'submitted_at' =>
                'datetime',

            'paid_at' =>
                'datetime',

            'failed_at' =>
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


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }


    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            PayrollPaymentBatch::class,
            'payroll_payment_batch_id'
        );
    }


    public function payrollRunItem(): BelongsTo
    {
        return $this->belongsTo(
            PayrollRunItem::class
        );
    }


    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class
        );
    }


    public function employeeBankAccount(): BelongsTo
    {
        return $this->belongsTo(
            EmployeeBankAccount::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForBatch(
        Builder $query,
        ?int $batchId
    ): Builder {
        if (!$batchId) {
            return $query;
        }

        return $query->where(
            $this->qualifyColumn(
                'payroll_payment_batch_id'
            ),
            $batchId
        );
    }


    public function scopeForEmployee(
        Builder $query,
        ?int $employeeId
    ): Builder {
        if (!$employeeId) {
            return $query;
        }

        return $query->where(
            $this->qualifyColumn('employee_id'),
            $employeeId
        );
    }


    public function scopeOfStatus(
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


    public function scopeReady(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('status'),
            'ready'
        );
    }


    public function scopeExceptions(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('status'),
            'exception'
        );
    }


    public function scopePaid(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('status'),
            'paid'
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
            'pending' =>
                'في انتظار الفحص',

            'ready' =>
                'جاهز للتحويل',

            'exception' =>
                'توجد مشكلة',

            'submitted' =>
                'تم الإرسال',

            'paid' =>
                'تم التحويل',

            'failed' =>
                'فشل التحويل',

            'excluded' =>
                'مستبعد',

            'cancelled' =>
                'ملغى',

            default =>
                'غير محدد',
        };
    }


    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'bank_transfer' =>
                'تحويل بنكي',

            'cash' =>
                'نقدي',

            'cheque' =>
                'شيك',

            'wallet' =>
                'محفظة إلكترونية',

            default =>
                'غير محدد',
        };
    }


    /*
    |--------------------------------------------------------------------------
    | إخفاء البيانات الحساسة
    |--------------------------------------------------------------------------
    */

    public function getMaskedAccountNumberAttribute(): ?string
    {
        if (!$this->account_number_last_four) {
            return null;
        }

        return '•••• •••• '
            . $this->account_number_last_four;
    }


    public function getMaskedIbanAttribute(): ?string
    {
        if (!$this->iban_last_four) {
            return null;
        }

        return 'SA•• •••• •••• •••• •••• '
            . $this->iban_last_four;
    }


    /*
    |--------------------------------------------------------------------------
    | Readiness
    |--------------------------------------------------------------------------
    */

    public function getIsReadyForTransferAttribute(): bool
    {
        return $this->status === 'ready'
            && $this->payment_method === 'bank_transfer'
            && filled($this->bank_name)
            && filled($this->account_holder_name)
            && filled($this->iban)
            && (float) $this->amount > 0;
    }


    public function isEditable(): bool
    {
        return $this->batch?->status === 'draft';
    }


    public function hasException(): bool
    {
        return $this->status === 'exception';
    }


    public function markReady(): void
    {
        $this->forceFill([
            'status' => 'ready',
            'exception_code' => null,
            'exception_message' => null,
            'failed_at' => null,
        ])->save();
    }


    public function markException(
        string $code,
        string $message
    ): void {
        $this->forceFill([
            'status' => 'exception',
            'exception_code' => $code,
            'exception_message' => $message,
        ])->save();
    }


    public function markSubmitted(
        ?string $bankReference = null
    ): void {
        $this->forceFill([
            'status' => 'submitted',
            'submitted_at' => now(),
            'bank_transaction_reference' =>
                $bankReference,
            'exception_code' => null,
            'exception_message' => null,
        ])->save();
    }


    public function markPaid(
        ?string $bankReference = null
    ): void {
        $this->forceFill([
            'status' => 'paid',
            'paid_at' => now(),
            'failed_at' => null,
            'bank_transaction_reference' =>
                $bankReference
                ?: $this->bank_transaction_reference,
            'exception_code' => null,
            'exception_message' => null,
        ])->save();
    }


    public function markFailed(
        string $message,
        ?string $code = null
    ): void {
        $this->forceFill([
            'status' => 'failed',
            'failed_at' => now(),
            'exception_code' =>
                $code ?: 'bank_transfer_failed',
            'exception_message' => $message,
        ])->save();
    }
}