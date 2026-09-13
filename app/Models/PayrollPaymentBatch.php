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

class PayrollPaymentBatch extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;


    protected $fillable = [
        'uuid',
        'tenant_id',
        'payroll_run_id',

        'batch_number',
        'payment_reference',
        'name',
        'payment_date',
        'currency_code',
        'file_format',
        'status',

        'employees_count',
        'ready_employees_count',
        'exception_employees_count',

        'total_amount',
        'successful_amount',
        'failed_amount',

        'source_bank_name',
        'source_bank_code',
        'source_account_number',
        'source_account_last_four',
        'source_iban',
        'source_iban_last_four',
        'source_swift_code',

        'file_disk',
        'file_path',
        'file_name',
        'file_mime_type',
        'file_size',
        'file_checksum',

        'validated_at',
        'validated_by',

        'generated_at',
        'generated_by',

        'submitted_at',
        'submitted_by',

        'completed_at',
        'completed_by',

        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',

        'notes',

        'created_by',
        'updated_by',
        'metadata',
    ];


    protected $hidden = [
        'source_account_number',
        'source_iban',
    ];


    protected $appends = [
        'status_label',
        'file_format_label',
        'masked_source_account_number',
        'masked_source_iban',
        'can_validate',
        'can_generate',
        'can_submit',
        'can_complete',
        'can_cancel',
    ];


    protected static function booted(): void
    {
        static::creating(function (
            PayrollPaymentBatch $batch
        ) {
            if (!$batch->uuid) {
                $batch->uuid = (string) Str::uuid();
            }
        });
    }


    protected function casts(): array
    {
        return [
            /*
             * تشفير بيانات حساب الشركة داخل قاعدة البيانات.
             */
            'source_account_number' =>
                'encrypted',

            'source_iban' =>
                'encrypted',

            'payment_date' =>
                'date',

            'employees_count' =>
                'integer',

            'ready_employees_count' =>
                'integer',

            'exception_employees_count' =>
                'integer',

            'total_amount' =>
                'decimal:2',

            'successful_amount' =>
                'decimal:2',

            'failed_amount' =>
                'decimal:2',

            'file_size' =>
                'integer',

            'validated_at' =>
                'datetime',

            'generated_at' =>
                'datetime',

            'submitted_at' =>
                'datetime',

            'completed_at' =>
                'datetime',

            'cancelled_at' =>
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
            PayrollRun::class
        );
    }


    public function items(): HasMany
    {
        return $this->hasMany(
            PayrollPaymentBatchItem::class
        )->orderBy('id');
    }


    public function readyItems(): HasMany
    {
        return $this->items()
            ->where('status', 'ready');
    }


    public function exceptionItems(): HasMany
    {
        return $this->items()
            ->where('status', 'exception');
    }


    public function submittedItems(): HasMany
    {
        return $this->items()
            ->where('status', 'submitted');
    }


    public function paidItems(): HasMany
    {
        return $this->items()
            ->where('status', 'paid');
    }


    public function failedItems(): HasMany
    {
        return $this->items()
            ->where('status', 'failed');
    }


    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'validated_by'
        );
    }


    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'generated_by'
        );
    }


    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'submitted_by'
        );
    }


    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'completed_by'
        );
    }


    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'cancelled_by'
        );
    }


    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForPayrollRun(
        Builder $query,
        ?int $payrollRunId
    ): Builder {
        if (!$payrollRunId) {
            return $query;
        }

        return $query->where(
            $this->qualifyColumn('payroll_run_id'),
            $payrollRunId
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


    public function scopeForPaymentDate(
        Builder $query,
        mixed $from = null,
        mixed $to = null
    ): Builder {
        return $query
            ->when(
                $from,
                fn (Builder $query) =>
                    $query->whereDate(
                        $this->qualifyColumn('payment_date'),
                        '>=',
                        $from
                    )
            )
            ->when(
                $to,
                fn (Builder $query) =>
                    $query->whereDate(
                        $this->qualifyColumn('payment_date'),
                        '<=',
                        $to
                    )
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
            'draft' =>
                'مسودة',

            'validated' =>
                'تم الفحص',

            'generated' =>
                'تم إنشاء الملف',

            'submitted' =>
                'تم الإرسال للبنك',

            'processing' =>
                'قيد المعالجة',

            'completed' =>
                'مكتملة',

            'partially_completed' =>
                'مكتملة جزئيًا',

            'failed' =>
                'فشلت',

            'cancelled' =>
                'ملغاة',

            default =>
                'غير محدد',
        };
    }


    public function getFileFormatLabelAttribute(): string
    {
        return match ($this->file_format) {
            'bank_csv' =>
                'ملف بنكي CSV',

            'csv' =>
                'CSV',

            'txt' =>
                'TXT',

            'sif' =>
                'SIF',

            default =>
                strtoupper(
                    (string) $this->file_format
                ),
        };
    }


    /*
    |--------------------------------------------------------------------------
    | إخفاء البيانات الحساسة
    |--------------------------------------------------------------------------
    */

    public function getMaskedSourceAccountNumberAttribute(): ?string
    {
        if (!$this->source_account_last_four) {
            return null;
        }

        return '•••• •••• '
            . $this->source_account_last_four;
    }


    public function getMaskedSourceIbanAttribute(): ?string
    {
        if (!$this->source_iban_last_four) {
            return null;
        }

        return 'SA•• •••• •••• •••• •••• '
            . $this->source_iban_last_four;
    }


    /*
    |--------------------------------------------------------------------------
    | حالات الإجراءات
    |--------------------------------------------------------------------------
    */

    public function getCanValidateAttribute(): bool
    {
        return $this->status === 'draft';
    }


    public function getCanGenerateAttribute(): bool
    {
        return $this->status === 'validated'
            && $this->exception_employees_count === 0
            && $this->ready_employees_count > 0;
    }


    public function getCanSubmitAttribute(): bool
    {
        return $this->status === 'generated'
            && filled($this->file_path);
    }


    public function getCanCompleteAttribute(): bool
    {
        return in_array(
            $this->status,
            [
                'submitted',
                'processing',
            ],
            true
        );
    }


    public function getCanCancelAttribute(): bool
    {
        return !in_array(
            $this->status,
            [
                'completed',
                'partially_completed',
                'cancelled',
            ],
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }


    public function hasExceptions(): bool
    {
        return $this->exception_employees_count > 0;
    }


    public function hasGeneratedFile(): bool
    {
        return filled(
            $this->file_path
        );
    }


    public function recalculateTotals(): void
    {
        $itemsQuery = $this->items();

        $this->forceFill([
            'employees_count' =>
                (clone $itemsQuery)
                    ->whereNotIn('status', [
                        'excluded',
                        'cancelled',
                    ])
                    ->count(),

            'ready_employees_count' =>
                (clone $itemsQuery)
                    ->where('status', 'ready')
                    ->count(),

            'exception_employees_count' =>
                (clone $itemsQuery)
                    ->where('status', 'exception')
                    ->count(),

            'total_amount' =>
                (clone $itemsQuery)
                    ->whereNotIn('status', [
                        'excluded',
                        'cancelled',
                    ])
                    ->sum('amount'),

            'successful_amount' =>
                (clone $itemsQuery)
                    ->where('status', 'paid')
                    ->sum('amount'),

            'failed_amount' =>
                (clone $itemsQuery)
                    ->where('status', 'failed')
                    ->sum('amount'),
        ])->save();
    }
}