<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Holiday extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;


    protected $fillable = [
        'uuid',
        'tenant_id',
        'branch_id',
        'code',
        'name',
        'name_en',
        'type',
        'start_date',
        'end_date',
        'is_paid',
        'exclude_from_leave_days',
        'affects_attendance',
        'is_recurring',
        'is_active',
        'metadata',
    ];


    protected function casts(): array
    {
        return [
            'start_date' =>
                'date',

            'end_date' =>
                'date',

            'is_paid' =>
                'boolean',

            'exclude_from_leave_days' =>
                'boolean',

            'affects_attendance' =>
                'boolean',

            'is_recurring' =>
                'boolean',

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


    protected static function booted(): void
    {
        static::creating(
            function (Holiday $holiday) {
                if (!$holiday->uuid) {
                    $holiday->uuid =
                        (string) Str::uuid();
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function branch(): BelongsTo
    {
        return $this->belongsTo(
            Branch::class
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
            $this->qualifyColumn(
                'is_active'
            ),
            true
        );
    }


    /**
     * العطلات المطبقة على فرع محدد.
     *
     * العطلة التي لا تحتوي branch_id
     * تطبق على جميع فروع الشركة.
     */
    public function scopeApplicableToBranch(
        Builder $query,
        ?int $branchId
    ): Builder {
        return $query->where(
            function (Builder $query) use (
                $branchId
            ) {
                $query->whereNull(
                    'branch_id'
                );

                if ($branchId) {
                    $query->orWhere(
                        'branch_id',
                        $branchId
                    );
                }
            }
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


    /**
     * العطلات التي تتداخل مع فترة معينة.
     *
     * يستخدم هذا النطاق بصورة أساسية
     * لفحص التداخل عند الإضافة والتعديل.
     */
    public function scopeOverlapping(
        Builder $query,
        string $startDate,
        string $endDate
    ): Builder {
        return $query
            ->whereDate(
                'start_date',
                '<=',
                $endDate
            )
            ->whereDate(
                'end_date',
                '>=',
                $startDate
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function getDisplayNameAttribute(): string
    {
        if (
            app()->getLocale() === 'en' &&
            $this->name_en
        ) {
            return $this->name_en;
        }

        return $this->name;
    }


    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'public' =>
                'عطلة رسمية',

            'company' =>
                'عطلة داخلية للشركة',

            'national' =>
                'عطلة وطنية',

            'religious' =>
                'عطلة دينية',

            'other' =>
                'أخرى',

            default =>
                'غير محدد',
        };
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
            $this->start_date
                ->diffInDays(
                    $this->end_date
                ) + 1;
    }


    /**
     * هل العطلة مطبقة على الفرع؟
     */
    public function appliesToBranch(
        ?int $branchId
    ): bool {
        return
            $this->branch_id === null ||
            (
                $branchId !== null &&
                (int) $this->branch_id ===
                $branchId
            );
    }


    /**
     * هل التاريخ المحدد يقع داخل العطلة؟
     *
     * يدعم العطلات العادية والعطلات
     * المتكررة سنويًا.
     */
    public function coversDate(
        CarbonInterface $date
    ): bool {
        if (
            !$this->is_active ||
            !$this->start_date ||
            !$this->end_date
        ) {
            return false;
        }


        $date = $date
            ->copy()
            ->startOfDay();


        /*
         * عطلة غير متكررة.
         */
        if (!$this->is_recurring) {
            return $date->betweenIncluded(
                $this->start_date
                    ->copy()
                    ->startOfDay(),

                $this->end_date
                    ->copy()
                    ->endOfDay()
            );
        }


        /*
         * مقارنة الشهر واليوم فقط
         * للعطلات المتكررة سنويًا.
         */
        $dateMonthDay =
            $date->format('m-d');

        $startMonthDay =
            $this->start_date
                ->format('m-d');

        $endMonthDay =
            $this->end_date
                ->format('m-d');


        /*
         * فترة داخل السنة نفسها.
         *
         * مثال:
         * من 09-22 إلى 09-23.
         */
        if (
            $startMonthDay <=
            $endMonthDay
        ) {
            return
                $dateMonthDay >=
                $startMonthDay &&
                $dateMonthDay <=
                $endMonthDay;
        }


        /*
         * فترة تعبر نهاية السنة.
         *
         * مثال:
         * من 12-31 إلى 01-02.
         */
        return
            $dateMonthDay >=
            $startMonthDay ||
            $dateMonthDay <=
            $endMonthDay;
    }
}