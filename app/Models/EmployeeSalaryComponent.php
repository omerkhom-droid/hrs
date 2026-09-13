<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSalaryComponent extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;


    protected $fillable = [
        'tenant_id',
        'salary_structure_id',
        'salary_component_id',
        'amount',
        'percentage',
        'rate',
        'quantity',
        'formula',
        'is_active',
        'metadata',
    ];


    protected function casts(): array
    {
        return [
            'amount' =>
                'decimal:2',

            'percentage' =>
                'decimal:4',

            'rate' =>
                'decimal:2',

            'quantity' =>
                'decimal:4',

            'is_active' =>
                'boolean',

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


    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(
            EmployeeSalaryStructure::class,
            'salary_structure_id'
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
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('is_active'),
            true
        );
    }


    public function scopeForStructure(
        Builder $query,
        int $structureId
    ): Builder {
        return $query->where(
            $this->qualifyColumn('salary_structure_id'),
            $structureId
        );
    }


    public function scopeForComponent(
        Builder $query,
        int $componentId
    ): Builder {
        return $query->where(
            $this->qualifyColumn('salary_component_id'),
            $componentId
        );
    }


    public function scopeEarnings(
        Builder $query
    ): Builder {
        return $query->whereHas(
            'salaryComponent',
            function (Builder $query) {
                $query->where(
                    'type',
                    SalaryComponent::TYPE_EARNING
                );
            }
        );
    }


    public function scopeDeductions(
        Builder $query
    ): Builder {
        return $query->whereHas(
            'salaryComponent',
            function (Builder $query) {
                $query->where(
                    'type',
                    SalaryComponent::TYPE_DEDUCTION
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isEarning(): bool
    {
        return $this->salaryComponent?->type ===
            SalaryComponent::TYPE_EARNING;
    }


    public function isDeduction(): bool
    {
        return $this->salaryComponent?->type ===
            SalaryComponent::TYPE_DEDUCTION;
    }


    public function getEffectiveCalculationMethodAttribute(): string
    {
        return $this->salaryComponent?->calculation_method
            ?: SalaryComponent::METHOD_FIXED;
    }


    public function getEffectiveAmountAttribute(): float
    {
        $method =
            $this->effective_calculation_method;

        if ($method === SalaryComponent::METHOD_FIXED) {
            return round(
                (float) ($this->amount ?? 0),
                2
            );
        }

        if (
            $method ===
            SalaryComponent::METHOD_QUANTITY_RATE
        ) {
            return round(
                (float) ($this->quantity ?? 0) *
                (float) ($this->rate ?? 0),
                2
            );
        }

        /*
         * النسبة والمعادلة تحتاجان إلى سياق باقي
         * مكونات الراتب، وسيتم حسابهما داخل الخدمة.
         */
        return 0.0;
    }


    public function getDisplayValueAttribute(): string
    {
        $method =
            $this->effective_calculation_method;

        if ($method === SalaryComponent::METHOD_FIXED) {
            return number_format(
                (float) ($this->amount ?? 0),
                2
            );
        }

        if ($method === SalaryComponent::METHOD_PERCENTAGE) {
            return number_format(
                (float) ($this->percentage ?? 0),
                4
            ) . '%';
        }

        if ($method === SalaryComponent::METHOD_FORMULA) {
            return $this->formula ?: '-';
        }

        if (
            $method ===
            SalaryComponent::METHOD_QUANTITY_RATE
        ) {
            return number_format(
                (float) ($this->quantity ?? 0),
                4
            ) .
                ' × ' .
                number_format(
                    (float) ($this->rate ?? 0),
                    2
                );
        }

        return '-';
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


    public function belongsToSameTenant(): bool
    {
        if (
            !$this->salaryStructure ||
            !$this->salaryComponent
        ) {
            return false;
        }

        return
            (int) $this->tenant_id ===
            (int) $this->salaryStructure->tenant_id
            &&
            (int) $this->tenant_id ===
            (int) $this->salaryComponent->tenant_id;
    }
}