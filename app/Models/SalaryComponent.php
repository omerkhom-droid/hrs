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

class SalaryComponent extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;


    public const TYPE_EARNING =
        'earning';

    public const TYPE_DEDUCTION =
        'deduction';


    public const METHOD_FIXED =
        'fixed';

    public const METHOD_PERCENTAGE =
        'percentage';

    public const METHOD_FORMULA =
        'formula';

    public const METHOD_QUANTITY_RATE =
        'quantity_rate';


    public const TYPES = [
        self::TYPE_EARNING =>
            'استحقاق',

        self::TYPE_DEDUCTION =>
            'استقطاع',
    ];


    public const CATEGORIES = [
        'basic_salary' =>
            'الراتب الأساسي',

        'allowance' =>
            'بدل',

        'bonus' =>
            'مكافأة',

        'commission' =>
            'عمولة',

        'overtime' =>
            'عمل إضافي',

        'reimbursement' =>
            'تعويض مصروفات',

        'tax' =>
            'ضريبة',

        'insurance' =>
            'تأمينات',

        'loan' =>
            'قرض أو سلفة',

        'absence' =>
            'غياب',

        'penalty' =>
            'جزاء',

        'other' =>
            'أخرى',
    ];


    public const CALCULATION_METHODS = [
        self::METHOD_FIXED =>
            'مبلغ ثابت',

        self::METHOD_PERCENTAGE =>
            'نسبة مئوية',

        self::METHOD_FORMULA =>
            'معادلة حسابية',

        self::METHOD_QUANTITY_RATE =>
            'كمية × معدل',
    ];


    protected $fillable = [
        'tenant_id',
        'uuid',
        'code',
        'name',
        'name_en',
        'type',
        'category',
        'calculation_method',
        'percentage_base_component_id',
        'default_amount',
        'default_percentage',
        'default_rate',
        'formula',
        'is_taxable',
        'is_subject_to_insurance',
        'is_included_in_overtime_base',
        'is_proratable',
        'is_recurring',
        'requires_input',
        'affects_net_salary',
        'is_system',
        'is_active',
        'sort_order',
        'metadata',
    ];


    protected function casts(): array
    {
        return [
            'default_amount' =>
                'decimal:4',

            'default_percentage' =>
                'decimal:4',

            'default_rate' =>
                'decimal:4',

            'is_taxable' =>
                'boolean',

            'is_subject_to_insurance' =>
                'boolean',

            'is_included_in_overtime_base' =>
                'boolean',

            'is_proratable' =>
                'boolean',

            'is_recurring' =>
                'boolean',

            'requires_input' =>
                'boolean',

            'affects_net_salary' =>
                'boolean',

            'is_system' =>
                'boolean',

            'is_active' =>
                'boolean',

            'sort_order' =>
                'integer',

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
        static::creating(
            function (
                SalaryComponent $component
            ) {
                if (!$component->uuid) {
                    $component->uuid =
                        (string) Str::uuid();
                }
            }
        );

        static::saving(
            function (
                SalaryComponent $component
            ) {
                $component->code =
                    strtoupper(
                        trim(
                            (string) $component->code
                        )
                    );

                $component->name =
                    trim(
                        (string) $component->name
                    );

                $component->name_en =
                    filled(
                        $component->name_en
                    )
                        ? trim(
                            (string) $component
                                ->name_en
                        )
                        : null;

                $component->formula =
                    filled(
                        $component->formula
                    )
                        ? trim(
                            (string) $component
                                ->formula
                        )
                        : null;
            }
        );
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


    public function percentageBaseComponent(): BelongsTo
    {
        return $this->belongsTo(
            SalaryComponent::class,
            'percentage_base_component_id'
        );
    }


    public function percentageDependents(): HasMany
    {
        return $this->hasMany(
            SalaryComponent::class,
            'percentage_base_component_id'
        );
    }


    public function salaryStructureComponents(): HasMany
    {
        return $this->hasMany(
            EmployeeSalaryComponent::class,
            'salary_component_id'
        );
    }


    public function payrollItemComponents(): HasMany
    {
        return $this->hasMany(
            PayrollRunItemComponent::class,
            'salary_component_id'
        );
    }


    public function adjustments(): HasMany
    {
        return $this->hasMany(
            PayrollAdjustment::class,
            'salary_component_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn(
                'is_active'
            ),
            true
        );
    }


    public function scopeEarnings(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn(
                'type'
            ),
            self::TYPE_EARNING
        );
    }


    public function scopeDeductions(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn(
                'type'
            ),
            self::TYPE_DEDUCTION
        );
    }


    public function scopeOfType(
        Builder $query,
        ?string $type
    ): Builder {
        if (!$type) {
            return $query;
        }

        return $query->where(
            $this->qualifyColumn(
                'type'
            ),
            $type
        );
    }


    public function scopeOfCategory(
        Builder $query,
        ?string $category
    ): Builder {
        if (!$category) {
            return $query;
        }

        return $query->where(
            $this->qualifyColumn(
                'category'
            ),
            $category
        );
    }


    public function scopeOrdered(
        Builder $query
    ): Builder {
        return $query
            ->orderBy(
                $this->qualifyColumn(
                    'sort_order'
                )
            )
            ->orderBy(
                $this->qualifyColumn(
                    'name'
                )
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getDisplayNameAttribute(): string
    {
        if (
            app()->getLocale() === 'en' &&
            filled($this->name_en)
        ) {
            return $this->name_en;
        }

        return $this->name;
    }


    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[
            $this->type
        ] ?? 'غير محدد';
    }


    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[
            $this->category
        ] ?? 'غير محدد';
    }


    public function getCalculationMethodLabelAttribute(): string
    {
        return self::CALCULATION_METHODS[
            $this->calculation_method
        ] ?? 'غير محدد';
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


    public function usesFixedAmount(): bool
    {
        return $this
            ->calculation_method ===
            self::METHOD_FIXED;
    }


    public function usesPercentage(): bool
    {
        return $this
            ->calculation_method ===
            self::METHOD_PERCENTAGE;
    }


    public function usesFormula(): bool
    {
        return $this
            ->calculation_method ===
            self::METHOD_FORMULA;
    }


    public function usesQuantityRate(): bool
    {
        return $this
            ->calculation_method ===
            self::METHOD_QUANTITY_RATE;
    }


    public function isEditable(): bool
    {
        return !$this->is_system;
    }


    public function canBeArchived(): bool
    {
        if ($this->is_system) {
            return false;
        }

        return !$this
            ->salaryStructureComponents()
            ->where(
                'is_active',
                true
            )
            ->exists();
    }
}