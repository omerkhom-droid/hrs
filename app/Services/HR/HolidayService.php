<?php

namespace App\Services\HR;

use App\Models\Holiday;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class HolidayService
{
    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(
        Tenant $tenant,
        array $data
    ): Holiday {
        return DB::transaction(
            function () use (
                $tenant,
                $data
            ) {
                $data = array_merge(
                    [
                        'branch_id' =>
                            null,

                        'type' =>
                            'public',

                        'is_paid' =>
                            true,

                        'exclude_from_leave_days' =>
                            true,

                        'affects_attendance' =>
                            true,

                        'is_recurring' =>
                            false,

                        'is_active' =>
                            true,
                    ],
                    $data
                );


                $this->validatePeriod(
                    $data
                );


                $this->ensureNoOverlap(
                    tenant:
                        $tenant,

                    data:
                        $data
                );


                return Holiday::create([
                    ...$data,

                    'tenant_id' =>
                        $tenant->id,
                ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        Tenant $tenant,
        Holiday $holiday,
        array $data
    ): Holiday {
        $this->ensureHolidayTenant(
            $tenant,
            $holiday
        );


        return DB::transaction(
            function () use (
                $tenant,
                $holiday,
                $data
            ) {
                $data = array_merge(
                    [
                        'branch_id' =>
                            $holiday->branch_id,

                        'type' =>
                            $holiday->type,

                        'is_paid' =>
                            $holiday->is_paid,

                        'exclude_from_leave_days' =>
                            $holiday
                                ->exclude_from_leave_days,

                        'affects_attendance' =>
                            $holiday
                                ->affects_attendance,

                        'is_recurring' =>
                            $holiday->is_recurring,

                        'is_active' =>
                            $holiday->is_active,
                    ],
                    $data
                );


                /*
                 * لا يسمح بتغيير الشركة أو UUID.
                 */
                unset(
                    $data['tenant_id'],
                    $data['uuid']
                );


                $this->validatePeriod(
                    $data
                );


                $this->ensureNoOverlap(
                    tenant:
                        $tenant,

                    data:
                        $data,

                    ignoreHoliday:
                        $holiday
                );


                $holiday->fill(
                    $data
                );


                $holiday->save();


                return $holiday->refresh();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function delete(
        Tenant $tenant,
        Holiday $holiday
    ): void {
        $this->ensureHolidayTenant(
            $tenant,
            $holiday
        );


        DB::transaction(
            function () use ($holiday) {
                $holiday->delete();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Date Lookup
    |--------------------------------------------------------------------------
    */

    /**
     * البحث عن العطلات المطبقة على تاريخ وفرع.
     */
    public function holidaysForDate(
        Tenant $tenant,
        CarbonImmutable $date,
        ?int $branchId = null
    ) {
        return Holiday::query()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->active()
            ->applicableToBranch(
                $branchId
            )
            ->get()
            ->filter(
                fn (Holiday $holiday) =>
                    $holiday->coversDate(
                        $date
                    )
            )
            ->values();
    }


    /**
     * هل التاريخ عطلة تؤثر على الحضور؟
     */
    public function isAttendanceHoliday(
        Tenant $tenant,
        CarbonImmutable $date,
        ?int $branchId = null
    ): bool {
        return $this
            ->holidaysForDate(
                tenant:
                    $tenant,

                date:
                    $date,

                branchId:
                    $branchId
            )
            ->contains(
                fn (Holiday $holiday) =>
                    $holiday
                        ->affects_attendance
            );
    }


    /**
     * هل يجب استبعاد التاريخ
     * من عدد أيام الإجازة؟
     */
    public function isExcludedFromLeave(
        Tenant $tenant,
        CarbonImmutable $date,
        ?int $branchId = null
    ): bool {
        return $this
            ->holidaysForDate(
                tenant:
                    $tenant,

                date:
                    $date,

                branchId:
                    $branchId
            )
            ->contains(
                fn (Holiday $holiday) =>
                    $holiday
                        ->exclude_from_leave_days
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    private function validatePeriod(
        array $data
    ): void {
        $startDate = CarbonImmutable::parse(
            $data['start_date']
        )->startOfDay();


        $endDate = CarbonImmutable::parse(
            $data['end_date']
        )->startOfDay();


        if (
            $endDate->lessThan(
                $startDate
            )
        ) {
            throw ValidationException::withMessages([
                'end_date' =>
                    'تاريخ النهاية يجب أن يساوي أو يلي تاريخ البداية.',
            ]);
        }


        $durationDays =
            (int) $startDate
                ->diffInDays(
                    $endDate
                ) + 1;


        /*
         * العطلة المتكررة سنويًا لا يمكن
         * أن تتجاوز دورة سنة كاملة.
         */
        if (
            !empty(
                $data['is_recurring']
            ) &&
            $durationDays > 366
        ) {
            throw ValidationException::withMessages([
                'end_date' =>
                    'مدة العطلة المتكررة لا يمكن أن تتجاوز سنة واحدة.',
            ]);
        }


        /*
         * حماية من إدخال فترة غير منطقية.
         */
        if ($durationDays > 3660) {
            throw ValidationException::withMessages([
                'end_date' =>
                    'مدة العطلة طويلة جدًا وغير منطقية.',
            ]);
        }
    }


    private function ensureNoOverlap(
        Tenant $tenant,
        array $data,
        ?Holiday $ignoreHoliday = null
    ): void {
        /*
         * العطلة غير النشطة لا تؤثر
         * على جدول العمل.
         */
        if (
            isset($data['is_active']) &&
            !$data['is_active']
        ) {
            return;
        }


        $branchId =
            !empty($data['branch_id'])
                ? (int) $data['branch_id']
                : null;


        $startDate = CarbonImmutable::parse(
            $data['start_date']
        )->startOfDay();


        $endDate = CarbonImmutable::parse(
            $data['end_date']
        )->startOfDay();


        $isRecurring =
            !empty(
                $data['is_recurring']
            );


        $candidates = Holiday::query()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->active()
            ->when(
                $ignoreHoliday,
                fn ($query) =>
                    $query->whereKeyNot(
                        $ignoreHoliday->id
                    )
            )
            ->when(
                $branchId,
                function ($query) use (
                    $branchId
                ) {
                    /*
                     * عطلة الفرع تتعارض مع:
                     * - عطلة جميع الفروع.
                     * - عطلة الفرع نفسه.
                     */
                    $query->where(
                        function ($query) use (
                            $branchId
                        ) {
                            $query
                                ->whereNull(
                                    'branch_id'
                                )
                                ->orWhere(
                                    'branch_id',
                                    $branchId
                                );
                        }
                    );
                }
            )
            ->get();


        foreach ($candidates as $candidate) {
            if (
                !$this->periodsOverlap(
                    newStart:
                        $startDate,

                    newEnd:
                        $endDate,

                    newRecurring:
                        $isRecurring,

                    existingHoliday:
                        $candidate
                )
            ) {
                continue;
            }


            throw ValidationException::withMessages([
                'start_date' =>
                    'تتداخل فترة العطلة مع عطلة مسجلة مسبقًا: ' .
                    $candidate->name,
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Overlap Helpers
    |--------------------------------------------------------------------------
    */

    private function periodsOverlap(
        CarbonImmutable $newStart,
        CarbonImmutable $newEnd,
        bool $newRecurring,
        Holiday $existingHoliday
    ): bool {
        $existingStart = CarbonImmutable::parse(
            $existingHoliday->start_date
        )->startOfDay();


        $existingEnd = CarbonImmutable::parse(
            $existingHoliday->end_date
        )->startOfDay();


        $existingRecurring =
            (bool) $existingHoliday
                ->is_recurring;


        /*
         * فترتان عاديتان غير متكررتين.
         */
        if (
            !$newRecurring &&
            !$existingRecurring
        ) {
            return
                $newStart->lessThanOrEqualTo(
                    $existingEnd
                ) &&
                $newEnd->greaterThanOrEqualTo(
                    $existingStart
                );
        }


        /*
         * فترتان متكررتان سنويًا.
         */
        if (
            $newRecurring &&
            $existingRecurring
        ) {
            $newDays =
                $this->monthDayKeys(
                    $newStart,
                    $newEnd
                );


            $existingDays =
                $this->monthDayKeys(
                    $existingStart,
                    $existingEnd
                );


            return !empty(
                array_intersect(
                    $newDays,
                    $existingDays
                )
            );
        }


        /*
         * العطلة الجديدة متكررة،
         * والعطلة الموجودة غير متكررة.
         */
        if ($newRecurring) {
            $newDays =
                $this->monthDayKeys(
                    $newStart,
                    $newEnd
                );


            foreach (
                CarbonPeriod::create(
                    $existingStart,
                    '1 day',
                    $existingEnd
                ) as $date
            ) {
                if (
                    in_array(
                        $date->format('m-d'),
                        $newDays,
                        true
                    )
                ) {
                    return true;
                }
            }


            return false;
        }


        /*
         * العطلة الموجودة متكررة،
         * والعطلة الجديدة غير متكررة.
         */
        $existingDays =
            $this->monthDayKeys(
                $existingStart,
                $existingEnd
            );


        foreach (
            CarbonPeriod::create(
                $newStart,
                '1 day',
                $newEnd
            ) as $date
        ) {
            if (
                in_array(
                    $date->format('m-d'),
                    $existingDays,
                    true
                )
            ) {
                return true;
            }
        }


        return false;
    }


    private function monthDayKeys(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate
    ): array {
        $keys = [];


        foreach (
            CarbonPeriod::create(
                $startDate,
                '1 day',
                $endDate
            ) as $date
        ) {
            $keys[] =
                $date->format('m-d');
        }


        return array_values(
            array_unique(
                $keys
            )
        );
    }


    public function excludedLeaveDates(
        Tenant $tenant,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        ?int $branchId = null
    ): Collection {
        $startDate = CarbonImmutable::instance(
            $startDate
        )->startOfDay();

        $endDate = CarbonImmutable::instance(
            $endDate
        )->startOfDay();

        if ($endDate->lt($startDate)) {
            return collect();
        }

        $holidays = Holiday::query()
            ->where(
                'tenant_id',
                $tenant->id
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'exclude_from_leave_days',
                true
            )
            ->where(function ($query) use ($branchId) {

                $query->whereNull(
                    'branch_id'
                );

                if ($branchId) {
                    $query->orWhere(
                        'branch_id',
                        $branchId
                    );
                }
            })
            ->where(function ($query) use (
                $startDate,
                $endDate
            ) {
                /*
                 * العطلات المتكررة تُفحص لاحقًا
                 * باستخدام coversDate().
                 */
                $query->where(
                    'is_recurring',
                    true
                )->orWhere(function ($query) use (
                    $startDate,
                    $endDate
                ) {
                    $query
                        ->whereDate(
                            'start_date',
                            '<=',
                            $endDate->toDateString()
                        )
                        ->whereDate(
                            'end_date',
                            '>=',
                            $startDate->toDateString()
                        );
                });
            })
            ->get();

        $excludedDates = collect();

        foreach (
            CarbonPeriod::create(
                $startDate,
                $endDate
            )
            as $periodDate
        ) {
            $date = CarbonImmutable::instance(
                $periodDate
            )->startOfDay();

            $isHoliday = $holidays->contains(
                function (Holiday $holiday) use ($date) {
                    return $holiday->coversDate(
                        $date
                    );
                }
            );

            if ($isHoliday) {
                $excludedDates->put(
                    $date->toDateString(),
                    true
                );
            }
        }

        return $excludedDates;
    }

    /*
    |--------------------------------------------------------------------------
    | Tenant Protection
    |--------------------------------------------------------------------------
    */

    private function ensureHolidayTenant(
        Tenant $tenant,
        Holiday $holiday
    ): void {
        if (
            (int) $holiday->tenant_id !==
            (int) $tenant->id
        ) {
            throw new DomainException(
                'العطلة لا تتبع الشركة الحالية.'
            );
        }
    }
}