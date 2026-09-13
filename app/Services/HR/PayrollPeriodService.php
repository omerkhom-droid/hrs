<?php

namespace App\Services\HR;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class PayrollPeriodService
{
    /*
    |--------------------------------------------------------------------------
    | إنشاء فترة رواتب
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data,
        User $actor
    ): PayrollPeriod {
        $this->authorizeManager(
            $actor
        );

        return DB::transaction(function () use (
            $data,
            $actor
        ) {
            $tenantId =
                (int) $actor->tenant_id;

            $code = $this->generateCode(
                (int) $data['year'],
                (int) $data['month']
            );

            $exists = PayrollPeriod::query()
                ->where(
                    'tenant_id',
                    $tenantId
                )
                ->where(function ($query) use (
                    $code,
                    $data
                ) {
                    $query
                        ->where(
                            'code',
                            $code
                        )
                        ->orWhere(function ($query) use (
                            $data
                        ) {
                            $query
                                ->where(
                                    'year',
                                    $data['year']
                                )
                                ->where(
                                    'month',
                                    $data['month']
                                );
                        });
                })
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw new DomainException(
                    'توجد فترة رواتب مسجلة لهذا الشهر والسنة.'
                );
            }

            return PayrollPeriod::create([
                'tenant_id' =>
                    $tenantId,

                'code' =>
                    $code,

                'name' =>
                    $data['name'],

                'year' =>
                    $data['year'],

                'month' =>
                    $data['month'],

                'start_date' =>
                    $data['start_date'],

                'end_date' =>
                    $data['end_date'],

                'payment_date' =>
                    $data['payment_date'],

                'status' =>
                    PayrollPeriod::STATUS_DRAFT,

                'is_locked' =>
                    false,

                'created_by' =>
                    $actor->id,

                'metadata' =>
                    array_merge(
                        $data['metadata'] ?? [],
                        [
                            'created_source' =>
                                'web',

                            'created_at' =>
                                now()
                                    ->toIso8601String(),
                        ]
                    ),
            ])->refresh();
        });
    }


    /*
    |--------------------------------------------------------------------------
    | تعديل فترة الرواتب
    |--------------------------------------------------------------------------
    */

    public function update(
        PayrollPeriod $period,
        array $data,
        User $actor
    ): PayrollPeriod {
        $this->authorizeManager(
            $actor,
            $period
        );

        return DB::transaction(function () use (
            $period,
            $data,
            $actor
        ) {
            $period = $this->lockPeriod(
                $period,
                $actor
            );

            if (!$period->canBeEdited()) {
                throw new DomainException(
                    'يمكن تعديل فترة الرواتب وهي مسودة وغير مقفلة فقط.'
                );
            }

            $code = $this->generateCode(
                (int) $data['year'],
                (int) $data['month']
            );

            $duplicate =
                PayrollPeriod::query()
                    ->where(
                        'tenant_id',
                        $actor->tenant_id
                    )
                    ->whereKeyNot(
                        $period->id
                    )
                    ->where(function ($query) use (
                        $code,
                        $data
                    ) {
                        $query
                            ->where(
                                'code',
                                $code
                            )
                            ->orWhere(function ($query) use (
                                $data
                            ) {
                                $query
                                    ->where(
                                        'year',
                                        $data['year']
                                    )
                                    ->where(
                                        'month',
                                        $data['month']
                                    );
                            });
                    })
                    ->lockForUpdate()
                    ->exists();

            if ($duplicate) {
                throw new DomainException(
                    'توجد فترة رواتب أخرى لنفس الشهر والسنة.'
                );
            }

            $metadata =
                $period->metadata ?? [];

            $metadata['last_update'] = [
                'updated_by' =>
                    $actor->id,

                'updated_at' =>
                    now()->toIso8601String(),
            ];

            $period->update([
                'code' =>
                    $code,

                'name' =>
                    $data['name'],

                'year' =>
                    $data['year'],

                'month' =>
                    $data['month'],

                'start_date' =>
                    $data['start_date'],

                'end_date' =>
                    $data['end_date'],

                'payment_date' =>
                    $data['payment_date'],

                'metadata' =>
                    array_merge(
                        $metadata,
                        $data['metadata'] ?? []
                    ),
            ]);

            return $period->refresh();
        });
    }


    /*
    |--------------------------------------------------------------------------
    | فتح فترة الرواتب
    |--------------------------------------------------------------------------
    */

    public function open(
        PayrollPeriod $period,
        User $actor
    ): PayrollPeriod {
        $this->authorizeManager(
            $actor,
            $period
        );

        return DB::transaction(function () use (
            $period,
            $actor
        ) {
            $period = $this->lockPeriod(
                $period,
                $actor
            );

            if (!$period->canBeOpened()) {
                throw new DomainException(
                    'يمكن فتح فترة الرواتب عندما تكون مسودة وغير مقفلة فقط.'
                );
            }

            $metadata =
                $period->metadata ?? [];

            $metadata['opening'] = [
                'opened_by' =>
                    $actor->id,

                'opened_at' =>
                    now()->toIso8601String(),
            ];

            $period->update([
                'status' =>
                    PayrollPeriod::STATUS_OPEN,

                'metadata' =>
                    $metadata,
            ]);

            return $period->refresh();
        });
    }


    /*
    |--------------------------------------------------------------------------
    | قفل فترة الرواتب
    |--------------------------------------------------------------------------
    */

    public function lock(
        PayrollPeriod $period,
        User $actor
    ): PayrollPeriod {
        $this->authorizeApprover(
            $actor,
            $period
        );

        return DB::transaction(function () use (
            $period,
            $actor
        ) {
            $period = $this->lockPeriod(
                $period,
                $actor
            );

            if (!$period->canBeLocked()) {
                throw new DomainException(
                    'لا يمكن قفل فترة الرواتب في حالتها الحالية.'
                );
            }

            $period->update([
                'is_locked' =>
                    true,

                'locked_at' =>
                    now(),

                'locked_by' =>
                    $actor->id,

                'metadata' =>
                    array_merge(
                        $period->metadata ?? [],
                        [
                            'locking' => [
                                'locked_by' =>
                                    $actor->id,

                                'locked_at' =>
                                    now()
                                        ->toIso8601String(),
                            ],
                        ]
                    ),
            ]);

            return $period->refresh()
                ->load('lockedBy');
        });
    }


    /*
    |--------------------------------------------------------------------------
    | فك قفل الفترة
    |--------------------------------------------------------------------------
    */

    public function unlock(
        PayrollPeriod $period,
        User $actor,
        string $reason
    ): PayrollPeriod {
        $this->authorizeApprover(
            $actor,
            $period
        );

        return DB::transaction(function () use (
            $period,
            $actor,
            $reason
        ) {
            $period = $this->lockPeriod(
                $period,
                $actor
            );

            if (!$period->canBeUnlocked()) {
                throw new DomainException(
                    'لا يمكن فك قفل فترة الرواتب في حالتها الحالية.'
                );
            }

            if (trim($reason) === '') {
                throw new DomainException(
                    'سبب فك قفل الفترة مطلوب.'
                );
            }

            $history = data_get(
                $period->metadata,
                'unlock_history',
                []
            );

            $history[] = [
                'reason' =>
                    trim($reason),

                'unlocked_by' =>
                    $actor->id,

                'unlocked_at' =>
                    now()->toIso8601String(),
            ];

            $metadata =
                $period->metadata ?? [];

            $metadata['unlock_history'] =
                $history;

            $period->update([
                'is_locked' =>
                    false,

                'locked_at' =>
                    null,

                'locked_by' =>
                    null,

                'metadata' =>
                    $metadata,
            ]);

            return $period->refresh();
        });
    }


    /*
    |--------------------------------------------------------------------------
    | إلغاء الفترة
    |--------------------------------------------------------------------------
    */

    public function cancel(
        PayrollPeriod $period,
        User $actor,
        string $reason
    ): PayrollPeriod {
        $this->authorizeManager(
            $actor,
            $period
        );

        return DB::transaction(function () use (
            $period,
            $actor,
            $reason
        ) {
            $period = $this->lockPeriod(
                $period,
                $actor
            );

            if (!$period->canBeCancelled()) {
                throw new DomainException(
                    'لا يمكن إلغاء فترة الرواتب في حالتها الحالية.'
                );
            }

            if (trim($reason) === '') {
                throw new DomainException(
                    'سبب إلغاء الفترة مطلوب.'
                );
            }

            $hasActiveRuns =
                $period->runs()
                    ->whereNotIn(
                        'status',
                        [
                            PayrollRun::STATUS_CANCELLED,
                        ]
                    )
                    ->exists();

            if ($hasActiveRuns) {
                throw new DomainException(
                    'لا يمكن إلغاء الفترة لأنها تحتوي على مسير رواتب غير ملغي.'
                );
            }

            $period->update([
                'status' =>
                    PayrollPeriod::STATUS_CANCELLED,

                'metadata' =>
                    array_merge(
                        $period->metadata ?? [],
                        [
                            'cancellation' => [
                                'reason' =>
                                    trim($reason),

                                'cancelled_by' =>
                                    $actor->id,

                                'cancelled_at' =>
                                    now()
                                        ->toIso8601String(),
                            ],
                        ]
                    ),
            ]);

            return $period->refresh();
        });
    }


    /*
    |--------------------------------------------------------------------------
    | إغلاق الفترة نهائيًا
    |--------------------------------------------------------------------------
    */

    public function close(
        PayrollPeriod $period,
        User $actor
    ): PayrollPeriod {
        $this->authorizeApprover(
            $actor,
            $period
        );

        return DB::transaction(function () use (
            $period,
            $actor
        ) {
            $period = $this->lockPeriod(
                $period,
                $actor
            );

            if (
                !in_array(
                    $period->status,
                    [
                        PayrollPeriod::STATUS_PAID,
                        PayrollPeriod::STATUS_APPROVED,
                    ],
                    true
                )
            ) {
                throw new DomainException(
                    'يجب اعتماد أو صرف جميع المسيرات قبل إغلاق الفترة.'
                );
            }

            $activeRuns =
                $period->runs()
                    ->whereNotIn(
                        'status',
                        [
                            PayrollRun::STATUS_CANCELLED,
                        ]
                    )
                    ->get();

            if ($activeRuns->isEmpty()) {
                throw new DomainException(
                    'لا يمكن إغلاق فترة لا تحتوي على مسيرات.'
                );
            }

            $hasIncompleteRuns =
                $activeRuns->contains(
                    fn (PayrollRun $run) =>
                        !in_array(
                            $run->status,
                            [
                                PayrollRun::STATUS_APPROVED,
                                PayrollRun::STATUS_PAID,
                            ],
                            true
                        )
                );

            if ($hasIncompleteRuns) {
                throw new DomainException(
                    'يوجد مسير لم يتم اعتماده أو صرفه بعد.'
                );
            }

            $period->update([
                'status' =>
                    PayrollPeriod::STATUS_CLOSED,

                'is_locked' =>
                    true,

                'locked_at' =>
                    now(),

                'locked_by' =>
                    $actor->id,

                'metadata' =>
                    array_merge(
                        $period->metadata ?? [],
                        [
                            'closing' => [
                                'closed_by' =>
                                    $actor->id,

                                'closed_at' =>
                                    now()
                                        ->toIso8601String(),
                            ],
                        ]
                    ),
            ]);

            return $period->refresh()
                ->load('lockedBy');
        });
    }


    /*
    |--------------------------------------------------------------------------
    | حذف المسودة
    |--------------------------------------------------------------------------
    */

    public function delete(
        PayrollPeriod $period,
        User $actor
    ): void {
        $this->authorizeManager(
            $actor,
            $period
        );

        DB::transaction(function () use (
            $period,
            $actor
        ) {
            $period = $this->lockPeriod(
                $period,
                $actor
            );

            if (!$period->canBeDeleted()) {
                throw new DomainException(
                    'يمكن حذف فترة مسودة لا تحتوي على مسيرات فقط.'
                );
            }

            $period->delete();
        });
    }


    /*
    |--------------------------------------------------------------------------
    | مزامنة حالة الفترة مع المسيرات
    |--------------------------------------------------------------------------
    */

    public function refreshStatusFromRuns(
        PayrollPeriod $period
    ): PayrollPeriod {
        return DB::transaction(function () use (
            $period
        ) {
            $period =
                PayrollPeriod::query()
                    ->whereKey($period->id)
                    ->lockForUpdate()
                    ->firstOrFail();

            if (
                $period->is_locked ||
                in_array(
                    $period->status,
                    [
                        PayrollPeriod::STATUS_DRAFT,
                        PayrollPeriod::STATUS_CLOSED,
                        PayrollPeriod::STATUS_CANCELLED,
                    ],
                    true
                )
            ) {
                return $period;
            }

            $runs = $period->runs()
                ->whereNotIn(
                    'status',
                    [
                        PayrollRun::STATUS_CANCELLED,
                    ]
                )
                ->get();

            if ($runs->isEmpty()) {
                $status =
                    PayrollPeriod::STATUS_OPEN;
            } elseif (
                $runs->every(
                    fn (PayrollRun $run) =>
                        $run->isPaid()
                )
            ) {
                $status =
                    PayrollPeriod::STATUS_PAID;
            } elseif (
                $runs->every(
                    fn (PayrollRun $run) =>
                        in_array(
                            $run->status,
                            [
                                PayrollRun::STATUS_APPROVED,
                                PayrollRun::STATUS_PAID,
                            ],
                            true
                        )
                )
            ) {
                $status =
                    PayrollPeriod::STATUS_APPROVED;
            } elseif (
                $runs->contains(
                    fn (PayrollRun $run) =>
                        in_array(
                            $run->status,
                            [
                                PayrollRun::STATUS_CALCULATED,
                                PayrollRun::STATUS_REVIEW,
                            ],
                            true
                        )
                )
            ) {
                $status =
                    PayrollPeriod::STATUS_REVIEW;
            } else {
                $status =
                    PayrollPeriod::STATUS_PROCESSING;
            }

            $period->update([
                'status' =>
                    $status,
            ]);

            return $period->refresh();
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function generateCode(
        int $year,
        int $month
    ): string {
        return sprintf(
            'PAY-%04d-%02d',
            $year,
            $month
        );
    }


    private function lockPeriod(
        PayrollPeriod $period,
        User $actor
    ): PayrollPeriod {
        return PayrollPeriod::query()
            ->where(
                'tenant_id',
                $actor->tenant_id
            )
            ->whereKey($period->id)
            ->lockForUpdate()
            ->firstOrFail();
    }


    private function authorizeManager(
        User $actor,
        ?PayrollPeriod $period = null
    ): void {
        if (
            !$actor->tenant_id ||
            !$actor->can('payroll.manage')
        ) {
            throw new DomainException(
                'غير مصرح لك بإدارة فترات الرواتب.'
            );
        }

        $this->assertSameTenant(
            $actor,
            $period
        );
    }


    private function authorizeApprover(
        User $actor,
        ?PayrollPeriod $period = null
    ): void {
        if (
            !$actor->tenant_id ||
            !$actor->can('payroll.approve')
        ) {
            throw new DomainException(
                'غير مصرح لك باعتماد أو قفل فترات الرواتب.'
            );
        }

        $this->assertSameTenant(
            $actor,
            $period
        );
    }


    private function assertSameTenant(
        User $actor,
        ?PayrollPeriod $period
    ): void {
        if (
            $period &&
            (int) $period->tenant_id !==
            (int) $actor->tenant_id
        ) {
            throw new DomainException(
                'لا يمكن إدارة فترة رواتب تابعة لشركة أخرى.'
            );
        }
    }
}