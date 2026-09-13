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

class EmployeeLoan extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'request_number',
        'loan_type',
        'requested_amount',
        'approved_amount',
        'installments_count',
        'installment_amount',
        'paid_amount',
        'remaining_amount',
        'first_installment_date',
        'status',
        'reason',
        'employee_notes',
        'approval_notes',
        'rejection_reason',
        'cancellation_reason',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'cancelled_at',
        'completed_at',
        'approved_by',
        'rejected_by',
        'cancelled_by',
        'created_by',
        'metadata',
    ];

    protected $appends = [
        'status_label',
        'loan_type_label',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'installment_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',

            'installments_count' => 'integer',

            'first_installment_date' => 'date',

            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',

            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EmployeeLoan $loan) {
            if (!$loan->uuid) {
                $loan->uuid = (string) Str::uuid();
            }

            if (!$loan->request_number) {
                $loan->request_number =
                    self::generateRequestNumber(
                        (int) $loan->tenant_id
                    );
            }

            if ($loan->remaining_amount === null) {
                $loan->remaining_amount = 0;
            }

            if ($loan->paid_amount === null) {
                $loan->paid_amount = 0;
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
        return $this->belongsTo(Tenant::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(
            EmployeeLoanInstallment::class
        )->orderBy('installment_number');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'rejected_by'
        );
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'cancelled_by'
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

    public function scopeStatus(
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
        return $query->whereIn(
            $this->qualifyColumn('status'),
            ['approved', 'active']
        );
    }

    public function scopeSearch(
        Builder $query,
        ?string $search
    ): Builder {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(
            function (Builder $query) use ($search) {
                $query
                    ->where(
                        $this->qualifyColumn(
                            'request_number'
                        ),
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'employee',
                        fn (Builder $employeeQuery) =>
                            $employeeQuery->search($search)
                    );
            }
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
            'draft' => 'مسودة',
            'submitted' => 'بانتظار الاعتماد',
            'approved' => 'معتمدة',
            'rejected' => 'مرفوضة',
            'active' => 'قيد السداد',
            'completed' => 'مكتملة',
            'cancelled' => 'ملغاة',
            default => 'غير محدد',
        };
    }

    public function getLoanTypeLabelAttribute(): string
    {
        return match ($this->loan_type) {
            'salary_advance' => 'سلفة راتب',
            'personal_loan' => 'سلفة شخصية',
            'emergency_loan' => 'سلفة طارئة',
            default => 'سلفة مالية',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isApproved(): bool
    {
        return in_array(
            $this->status,
            ['approved', 'active'],
            true
        );
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeEdited(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeCancelled(): bool
    {
        return in_array(
            $this->status,
            ['draft', 'submitted', 'approved'],
            true
        );
    }

    public function refreshTotals(): self
    {
        $paidAmount = (float) $this->installments()
            ->sum('paid_amount');

        $approvedAmount =
            (float) ($this->approved_amount ?? 0);

        $remainingAmount = max(
            0,
            round($approvedAmount - $paidAmount, 2)
        );

        $status = $this->status;

        if (
            $approvedAmount > 0 &&
            $remainingAmount <= 0
        ) {
            $status = 'completed';
        } elseif (
            $paidAmount > 0 &&
            in_array(
                $status,
                ['approved', 'active'],
                true
            )
        ) {
            $status = 'active';
        }

        $this->forceFill([
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'status' => $status,
            'completed_at' =>
                $status === 'completed'
                    ? ($this->completed_at ?? now())
                    : null,
        ])->save();

        return $this->refresh();
    }

    private static function generateRequestNumber(
        int $tenantId
    ): string {
        do {
            $number = sprintf(
                'LN-%s-%s',
                now()->format('Y'),
                strtoupper(Str::random(8))
            );
        } while (
            self::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('request_number', $number)
                ->exists()
        );

        return $number;
    }
}