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

class LeaveRequest extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'leave_type_id',
        'replacement_employee_id',
        'start_date',
        'end_date',
        'return_date',
        'start_session',
        'end_session',
        'requested_amount',
        'approved_amount',
        'status',
        'reason',
        'handover_notes',
        'contact_during_leave',
        'attachment_path',
        'decision_notes',
        'current_approval_level',
        'required_approval_levels',
        'requested_at',
        'approved_at',
        'rejected_at',
        'cancelled_at',
        'requested_by',
        'approved_by',
        'cancelled_by',
        'created_by',
        'metadata',
    ];

    protected $appends = [
        'status_label',
        'status_color',
        'duration_label',
    ];

    protected static function booted(): void
    {
        static::creating(function (LeaveRequest $leaveRequest) {
            if (!$leaveRequest->uuid) {
                $leaveRequest->uuid =
                    (string) Str::uuid();
            }

            if (!$leaveRequest->status) {
                $leaveRequest->status =
                    self::STATUS_DRAFT;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'start_date' =>
                'date',

            'end_date' =>
                'date',

            'return_date' =>
                'date',

            'requested_amount' =>
                'decimal:2',

            'approved_amount' =>
                'decimal:2',

            'current_approval_level' =>
                'integer',

            'required_approval_levels' =>
                'integer',

            'requested_at' =>
                'datetime',

            'approved_at' =>
                'datetime',

            'rejected_at' =>
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

    public function replacementEmployee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'replacement_employee_id'
        );
    }

    public function days(): HasMany
    {
        return $this->hasMany(
            LeaveRequestDay::class
        )->orderBy('leave_date');
    }

    public function balanceTransactions(): HasMany
    {
        return $this->hasMany(
            LeaveBalanceTransaction::class
        )->latest('effective_date')
            ->latest('id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'requested_by'
        );
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
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

    public function scopeOfStatus(
        Builder $query,
        ?string $status
    ): Builder {
        if (
            !$status ||
            !in_array(
                $status,
                self::statuses(),
                true
            )
        ) {
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

    public function scopeOverlapping(
        Builder $query,
        string $startDate,
        string $endDate
    ): Builder {
        return $query->where(
            function (Builder $query) use (
                $startDate,
                $endDate
            ) {
                $query
                    ->whereDate(
                        $this->qualifyColumn('start_date'),
                        '<=',
                        $endDate
                    )
                    ->whereDate(
                        $this->qualifyColumn('end_date'),
                        '>=',
                        $startDate
                    );
            }
        );
    }

    public function scopeActiveDuring(
        Builder $query,
        string $date
    ): Builder {
        return $query
            ->approved()
            ->whereDate(
                $this->qualifyColumn('start_date'),
                '<=',
                $date
            )
            ->whereDate(
                $this->qualifyColumn('end_date'),
                '>=',
                $date
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT =>
                'مسودة',

            self::STATUS_PENDING =>
                'بانتظار الاعتماد',

            self::STATUS_APPROVED =>
                'معتمد',

            self::STATUS_REJECTED =>
                'مرفوض',

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

            self::STATUS_PENDING =>
                'warning',

            self::STATUS_APPROVED =>
                'success',

            self::STATUS_REJECTED =>
                'danger',

            self::STATUS_CANCELLED =>
                'dark',

            default =>
                'secondary',
        };
    }

    public function getDurationLabelAttribute(): string
    {
        $amount =
            (float) $this->requested_amount;

        $unit =
            $this->leaveType?->unit ?? 'day';

        if ($unit === 'hour') {
            return $this->formatAmount($amount) .
                ' ساعة';
        }

        return $this->formatAmount($amount) .
            ' يوم';
    }

    /*
    |--------------------------------------------------------------------------
    | Status Helpers
    |--------------------------------------------------------------------------
    */

    public function isDraft(): bool
    {
        return
            $this->status ===
            self::STATUS_DRAFT;
    }

    public function isPending(): bool
    {
        return
            $this->status ===
            self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return
            $this->status ===
            self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return
            $this->status ===
            self::STATUS_REJECTED;
    }

    public function isCancelled(): bool
    {
        return
            $this->status ===
            self::STATUS_CANCELLED;
    }

    public function canBeSubmitted(): bool
    {
        return $this->isDraft();
    }

    public function canBeReviewed(): bool
    {
        return $this->isPending();
    }

    public function canBeCancelled(): bool
    {
        if (
            $this->isRejected() ||
            $this->isCancelled()
        ) {
            return false;
        }

        if (
            $this->isApproved() &&
            $this->start_date?->isPast()
        ) {
            return false;
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | General Helpers
    |--------------------------------------------------------------------------
    */

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        ];
    }

    private function formatAmount(
        float $amount
    ): string {
        if (floor($amount) === $amount) {
            return (string) (int) $amount;
        }

        return number_format(
            $amount,
            2,
            '.',
            ''
        );
    }
}