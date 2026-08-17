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

class EmployeeContract extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'renewed_from_id',
        'contract_number',
        'contract_type',
        'status',
        'start_date',
        'end_date',
        'probation_end_date',
        'basic_salary',
        'housing_allowance',
        'transport_allowance',
        'other_allowances',
        'currency_code',
        'pay_frequency',
        'working_hours_per_day',
        'working_days_per_week',
        'annual_leave_days',
        'notice_period_days',
        'auto_renew',
        'renewal_notice_days',
        'signed_at',
        'activated_at',
        'activated_by',
        'termination_date',
        'termination_reason',
        'terminated_by',
        'terms',
        'notes',
        'metadata',
    ];

    protected $appends = [
        'contract_type_label',
        'status_label',
        'pay_frequency_label',
        'gross_salary',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'probation_end_date' => 'date',
            'termination_date' => 'date',
            'signed_at' => 'datetime',
            'activated_at' => 'datetime',
            'basic_salary' => 'decimal:2',
            'housing_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'other_allowances' => 'decimal:2',
            'working_hours_per_day' => 'decimal:2',
            'working_days_per_week' => 'integer',
            'annual_leave_days' => 'integer',
            'notice_period_days' => 'integer',
            'auto_renew' => 'boolean',
            'renewal_notice_days' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EmployeeContract $contract) {
            if (!$contract->uuid) {
                $contract->uuid = (string) Str::uuid();
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function renewedFrom(): BelongsTo
    {
        return $this->belongsTo(
            EmployeeContract::class,
            'renewed_from_id'
        );
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(
            EmployeeContract::class,
            'renewed_from_id'
        )->orderByDesc('start_date');
    }

    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'activated_by'
        );
    }

    public function terminatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'terminated_by'
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

        return $query->where(function (Builder $query) use ($search) {
            $query
                ->where('contract_number', 'like', "%{$search}%")
                ->orWhereHas('employee', function (Builder $query) use ($search) {
                    $query->search($search);
                });
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            $this->qualifyColumn('status'),
            'active'
        );
    }

    public function scopeExpiringBetween(
        Builder $query,
        mixed $from,
        mixed $to
    ): Builder {
        return $query->whereBetween(
            $this->qualifyColumn('end_date'),
            [$from, $to]
        );
    }

    public function getGrossSalaryAttribute(): string
    {
        return number_format(
            (float) $this->basic_salary
            + (float) $this->housing_allowance
            + (float) $this->transport_allowance
            + (float) $this->other_allowances,
            2,
            '.',
            ''
        );
    }

    public function getContractTypeLabelAttribute(): string
    {
        return match ($this->contract_type) {
            'indefinite' => 'غير محدد المدة',
            'fixed_term' => 'محدد المدة',
            'temporary' => 'مؤقت',
            'seasonal' => 'موسمي',
            'part_time' => 'دوام جزئي',
            'training' => 'تدريب',
            default => 'غير محدد',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'مسودة',
            'active' => 'فعال',
            'suspended' => 'موقوف',
            'expired' => 'منتهي',
            'terminated' => 'منهى',
            'cancelled' => 'ملغي',
            default => 'غير محدد',
        };
    }

    public function getPayFrequencyLabelAttribute(): string
    {
        return match ($this->pay_frequency) {
            'monthly' => 'شهري',
            'daily' => 'يومي',
            'hourly' => 'بالساعة',
            default => 'غير محدد',
        };
    }
}