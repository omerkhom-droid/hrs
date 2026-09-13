<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PayrollPeriod extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;


    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_REVIEW = 'review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';


    protected $fillable = [
        'uuid',
        'tenant_id',
        'code',
        'name',
        'year',
        'month',
        'start_date',
        'end_date',
        'payment_date',
        'status',
        'is_locked',
        'locked_at',
        'locked_by',
        'created_by',
        'metadata',
    ];


    protected function casts(): array
    {
        return [
            'year' =>
                'integer',

            'month' =>
                'integer',

            'start_date' =>
                'date',

            'end_date' =>
                'date',

            'payment_date' =>
                'date',

            'is_locked' =>
                'boolean',

            'locked_at' =>
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
            PayrollPeriod $period
        ) {
            if (!$period->uuid) {
                $period->uuid =
                    (string) Str::uuid();
            }

            if (!$period->status) {
                $period->status =
                    self::STATUS_DRAFT;
            }

            if ($period->is_locked === null) {
                $period->is_locked = false;
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


    public function runs(): HasMany
    {
        return $this->hasMany(
            PayrollRun::class,
            'payroll_period_id'
        )->orderByDesc('id');
    }


    public function activeRuns(): HasMany
    {
        return $this->runs()
            ->whereNotIn(
                'status',
                [
                    PayrollRun::STATUS_CANCELLED,
                ]
            );
    }


    public function adjustments(): HasMany
    {
        return $this->hasMany(
            PayrollAdjustment::class,
            'payroll_period_id'
        );
    }


    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'locked_by'
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

    public function scopeForYear(
        Builder $query,
        ?int $year
    ): Builder {
        if (!$year) {
            return $query;
        }

        return $query->where(
            $this->qualifyColumn('year'),
            $year
        );
    }


    public function scopeForMonth(
        Builder $query,
        ?int $month
    ): Builder {
        if (!$month) {
            return $query;
        }

        return $query->where(
            $this->qualifyColumn('month'),
            $month
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


    public function scopeDraft(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('status'),
            self::STATUS_DRAFT
        );
    }


    public function scopeOpen(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('status'),
            self::STATUS_OPEN
        );
    }


    public function scopeUnlocked(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('is_locked'),
            false
        );
    }


    public function scopeLocked(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('is_locked'),
            true
        );
    }


    public function scopeContainingDate(
        Builder $query,
        Carbon|string $date
    ): Builder {
        $date = Carbon::parse(
            $date
        )->toDateString();

        return $query
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
    | حالات الفترة
    |--------------------------------------------------------------------------
    */

    public function isDraft(): bool
    {
        return $this->status ===
            self::STATUS_DRAFT;
    }


    public function isOpen(): bool
    {
        return $this->status ===
            self::STATUS_OPEN;
    }


    public function isProcessing(): bool
    {
        return $this->status ===
            self::STATUS_PROCESSING;
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


    public function isClosed(): bool
    {
        return $this->status ===
            self::STATUS_CLOSED;
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
            !$this->is_locked &&
            $this->isDraft();
    }


    public function canBeOpened(): bool
    {
        return
            !$this->trashed() &&
            !$this->is_locked &&
            $this->isDraft();
    }


    public function canCreatePayrollRun(): bool
    {
        return
            !$this->trashed() &&
            !$this->is_locked &&
            $this->isOpen();
    }


    public function canBeLocked(): bool
    {
        return
            !$this->trashed() &&
            !$this->is_locked &&
            in_array(
                $this->status,
                [
                    self::STATUS_APPROVED,
                    self::STATUS_PAID,
                    self::STATUS_CLOSED,
                ],
                true
            );
    }


    public function canBeUnlocked(): bool
    {
        return
            !$this->trashed() &&
            $this->is_locked &&
            !$this->isClosed();
    }


    public function canBeCancelled(): bool
    {
        return
            !$this->trashed() &&
            !$this->is_locked &&
            in_array(
                $this->status,
                [
                    self::STATUS_DRAFT,
                    self::STATUS_OPEN,
                ],
                true
            );
    }


    public function canBeDeleted(): bool
    {
        return
            !$this->trashed() &&
            !$this->is_locked &&
            $this->isDraft() &&
            !$this->runs()->exists();
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function containsDate(
        Carbon|string $date
    ): bool {
        $date = Carbon::parse(
            $date
        )->startOfDay();

        if (
            !$this->start_date ||
            !$this->end_date
        ) {
            return false;
        }

        return $date->betweenIncluded(
            $this->start_date
                ->copy()
                ->startOfDay(),

            $this->end_date
                ->copy()
                ->startOfDay()
        );
    }


    public function getDurationDaysAttribute(): int
    {
        if (
            !$this->start_date ||
            !$this->end_date
        ) {
            return 0;
        }

        return
            $this->start_date->diffInDays(
                $this->end_date
            ) + 1;
    }


    public function getPeriodLabelAttribute(): string
    {
        $start = $this->start_date
            ? $this->start_date->format('Y-m-d')
            : '-';

        $end = $this->end_date
            ? $this->end_date->format('Y-m-d')
            : '-';

        return $start . ' — ' . $end;
    }


    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT =>
                'مسودة',

            self::STATUS_OPEN =>
                'مفتوحة',

            self::STATUS_PROCESSING =>
                'قيد المعالجة',

            self::STATUS_REVIEW =>
                'قيد المراجعة',

            self::STATUS_APPROVED =>
                'معتمدة',

            self::STATUS_PAID =>
                'تم الصرف',

            self::STATUS_CLOSED =>
                'مغلقة',

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

            self::STATUS_OPEN =>
                'primary',

            self::STATUS_PROCESSING =>
                'info',

            self::STATUS_REVIEW =>
                'warning',

            self::STATUS_APPROVED =>
                'success',

            self::STATUS_PAID =>
                'success',

            self::STATUS_CLOSED =>
                'dark',

            self::STATUS_CANCELLED =>
                'danger',

            default =>
                'secondary',
        };
    }


    public function getMonthNameAttribute(): string
    {
        return match ($this->month) {
            1 => 'يناير',
            2 => 'فبراير',
            3 => 'مارس',
            4 => 'أبريل',
            5 => 'مايو',
            6 => 'يونيو',
            7 => 'يوليو',
            8 => 'أغسطس',
            9 => 'سبتمبر',
            10 => 'أكتوبر',
            11 => 'نوفمبر',
            12 => 'ديسمبر',
            default => 'غير محدد',
        };
    }
}