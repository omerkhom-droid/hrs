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

class EmployeeSalaryStructure extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;


    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';


    protected $fillable = [
        'uuid',
        'tenant_id',
        'employee_id',
        'version',
        'effective_from',
        'effective_to',
        'currency_code',
        'basic_salary',
        'total_earnings',
        'total_deductions',
        'net_salary',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'created_by',
        'metadata',
    ];


    protected function casts(): array
    {
        return [
            'version' =>
                'integer',

            'effective_from' =>
                'date',

            'effective_to' =>
                'date',

            'basic_salary' =>
                'decimal:2',

            'total_earnings' =>
                'decimal:2',

            'total_deductions' =>
                'decimal:2',

            'net_salary' =>
                'decimal:2',

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
            EmployeeSalaryStructure $structure
        ) {
            if (!$structure->uuid) {
                $structure->uuid =
                    (string) Str::uuid();
            }

            if (!$structure->version) {
                $structure->version = 1;
            }

            if (!$structure->currency_code) {
                $structure->currency_code =
                    $structure->tenant?->currency_code
                    ?? 'SAR';
            }

            if (!$structure->status) {
                $structure->status =
                    self::STATUS_DRAFT;
            }

            if ($structure->basic_salary === null) {
                $structure->basic_salary = 0;
            }

            if ($structure->total_earnings === null) {
                $structure->total_earnings = 0;
            }

            if ($structure->total_deductions === null) {
                $structure->total_deductions = 0;
            }

            if ($structure->net_salary === null) {
                $structure->net_salary = 0;
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


    public function components(): HasMany
    {
        return $this->hasMany(
            EmployeeSalaryComponent::class,
            'salary_structure_id'
        )->orderBy('id');
    }


    public function activeComponents(): HasMany
    {
        return $this->components()
            ->where('is_active', true);
    }


    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
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


    public function scopeDraft(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('status'),
            self::STATUS_DRAFT
        );
    }


    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('status'),
            self::STATUS_ACTIVE
        );
    }

    public function scopeExpired(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('status'),
            self::STATUS_EXPIRED
        );
    }

    public function scopeCurrent(
        Builder $query,
        Carbon|string|null $date = null
    ): Builder {
        return $query
            ->active()
            ->effectiveAt($date)
            ->orderByDesc(
                $this->qualifyColumn('version')
            );
    }


    public function scopeEffectiveAt(
        Builder $query,
        Carbon|string|null $date = null
    ): Builder {
        $date = $date
            ? Carbon::parse($date)->toDateString()
            : now()->toDateString();

        return $query
            ->whereDate(
                $this->qualifyColumn('effective_from'),
                '<=',
                $date
            )
            ->where(function (
                Builder $query
            ) use ($date) {
                $query
                    ->whereNull(
                        $this->qualifyColumn('effective_to')
                    )
                    ->orWhereDate(
                        $this->qualifyColumn('effective_to'),
                        '>=',
                        $date
                    );
            });
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


    /*
    |--------------------------------------------------------------------------
    | الحالات
    |--------------------------------------------------------------------------
    */

    public function isDraft(): bool
    {
        return $this->status ===
            self::STATUS_DRAFT;
    }


    public function isActive(): bool
    {
        return $this->status ===
            self::STATUS_ACTIVE;
    }


    public function isExpired(): bool
    {
        return $this->status ===
            self::STATUS_EXPIRED;
    }


    public function isCancelled(): bool
    {
        return $this->status ===
            self::STATUS_CANCELLED;
    }


    public function isEffectiveAt(
        Carbon|string|null $date = null
    ): bool {
        $date = $date
            ? Carbon::parse($date)->startOfDay()
            : now()->startOfDay();

        if (!$this->effective_from) {
            return false;
        }

        if (
            $this->effective_from
                ->copy()
                ->startOfDay()
                ->gt($date)
        ) {
            return false;
        }

        if (
            $this->effective_to &&
            $this->effective_to
                ->copy()
                ->startOfDay()
                ->lt($date)
        ) {
            return false;
        }

        return true;
    }


    public function canBeEdited(): bool
    {
        return
            !$this->trashed() &&
            $this->isDraft();
    }


    public function canBeActivated(): bool
    {
        return
            !$this->trashed() &&
            $this->isDraft() &&
            $this->components()
                ->where('is_active', true)
                ->exists();
    }


    public function canBeCancelled(): bool
    {
        return
            !$this->trashed() &&
            in_array(
                $this->status,
                [
                    self::STATUS_DRAFT,
                    self::STATUS_ACTIVE,
                ],
                true
            );
    }


    /*
    |--------------------------------------------------------------------------
    | القيم المالية
    |--------------------------------------------------------------------------
    */

    public function getGrossSalaryAttribute(): float
    {
        return round(
            (float) $this->total_earnings,
            2
        );
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


    public function getCalculatedNetSalaryAttribute(): float
    {
        return round(
            (float) $this->total_earnings -
            (float) $this->total_deductions,
            2
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
            self::STATUS_DRAFT =>
                'مسودة',

            self::STATUS_ACTIVE =>
                'نشط',

            self::STATUS_EXPIRED =>
                'منتهي',

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
                'warning',

            self::STATUS_ACTIVE =>
                'success',

            self::STATUS_EXPIRED =>
                'secondary',

            self::STATUS_CANCELLED =>
                'danger',

            default =>
                'secondary',
        };
    }

    public function getPeriodLabelAttribute(): string
    {
        $from = $this->effective_from
            ? $this->effective_from->format('Y-m-d')
            : '-';

        $to = $this->effective_to
            ? $this->effective_to->format('Y-m-d')
            : 'مستمر';

        return $from . ' — ' . $to;
    }
}