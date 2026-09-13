<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PayrollRunItemComponent extends Model
{
    use HasFactory;
    use BelongsToTenant;


    /*
    |--------------------------------------------------------------------------
    | نوع المكون
    |--------------------------------------------------------------------------
    */

    public const TYPE_EARNING = 'earning';
    public const TYPE_DEDUCTION = 'deduction';


    /*
    |--------------------------------------------------------------------------
    | مصدر المكون
    |--------------------------------------------------------------------------
    */

    public const SOURCE_SALARY_STRUCTURE =
        'salary_structure';

    public const SOURCE_ATTENDANCE =
        'attendance';

    public const SOURCE_OVERTIME =
        'overtime';

    public const SOURCE_LEAVE =
        'leave';

    public const SOURCE_MANUAL =
        'manual';

    public const SOURCE_SYSTEM =
        'system';


    protected $fillable = [
        'tenant_id',
        'payroll_item_id',
        'salary_component_id',
        'component_code',
        'component_name',
        'type',
        'category',
        'source',
        'quantity',
        'rate',
        'percentage',
        'amount',
        'is_taxable',
        'is_subject_to_insurance',
        'reference_type',
        'reference_id',
        'metadata',
    ];


    protected function casts(): array
    {
        return [
            'quantity' =>
                'decimal:4',

            'rate' =>
                'decimal:2',

            'percentage' =>
                'decimal:4',

            'amount' =>
                'decimal:2',

            'is_taxable' =>
                'boolean',

            'is_subject_to_insurance' =>
                'boolean',

            'metadata' =>
                'array',

            'created_at' =>
                'datetime',

            'updated_at' =>
                'datetime',
        ];
    }


    protected static function booted(): void
    {
        static::creating(function (
            PayrollRunItemComponent $component
        ) {
            if (!$component->source) {
                $component->source =
                    self::SOURCE_SALARY_STRUCTURE;
            }

            if ($component->quantity === null) {
                $component->quantity = 0;
            }

            if ($component->rate === null) {
                $component->rate = 0;
            }

            if ($component->percentage === null) {
                $component->percentage = 0;
            }

            if ($component->amount === null) {
                $component->amount = 0;
            }

            if ($component->is_taxable === null) {
                $component->is_taxable = false;
            }

            if (
                $component
                    ->is_subject_to_insurance ===
                null
            ) {
                $component
                    ->is_subject_to_insurance =
                    false;
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


    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(
            PayrollRunItem::class,
            'payroll_item_id'
        );
    }


    public function salaryComponent(): BelongsTo
    {
        return $this->belongsTo(
            SalaryComponent::class,
            'salary_component_id'
        );
    }


    /*
     * السجل الذي نتج عنه المكون، مثل:
     * طلب أوفرتايم أو سجل حضور أو تسوية.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo(
            'reference'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForPayrollItem(
        Builder $query,
        int $payrollItemId
    ): Builder {
        return $query->where(
            $this->qualifyColumn(
                'payroll_item_id'
            ),
            $payrollItemId
        );
    }


    public function scopeEarnings(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('type'),
            self::TYPE_EARNING
        );
    }


    public function scopeDeductions(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('type'),
            self::TYPE_DEDUCTION
        );
    }


    public function scopeFromSource(
        Builder $query,
        ?string $source
    ): Builder {
        if (!$source) {
            return $query;
        }

        return $query->where(
            $this->qualifyColumn('source'),
            $source
        );
    }


    public function scopeTaxable(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('is_taxable'),
            true
        );
    }


    public function scopeSubjectToInsurance(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn(
                'is_subject_to_insurance'
            ),
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isEarning(): bool
    {
        return $this->type ===
            self::TYPE_EARNING;
    }


    public function isDeduction(): bool
    {
        return $this->type ===
            self::TYPE_DEDUCTION;
    }


    public function getSignedAmountAttribute(): float
    {
        $amount = round(
            (float) $this->amount,
            2
        );

        return $this->isDeduction()
            ? -$amount
            : $amount;
    }


    public function getTypeLabelAttribute(): string
    {
        return $this->isEarning()
            ? 'استحقاق'
            : 'استقطاع';
    }


    public function getTypeColorAttribute(): string
    {
        return $this->isEarning()
            ? 'success'
            : 'danger';
    }


    public function getSourceLabelAttribute(): string
    {
        return match ($this->source) {
            self::SOURCE_SALARY_STRUCTURE =>
                'هيكل الراتب',

            self::SOURCE_ATTENDANCE =>
                'الحضور والانصراف',

            self::SOURCE_OVERTIME =>
                'العمل الإضافي',

            self::SOURCE_LEAVE =>
                'الإجازات',

            self::SOURCE_MANUAL =>
                'إدخال يدوي',

            self::SOURCE_SYSTEM =>
                'النظام',

            default =>
                'غير محدد',
        };
    }
}