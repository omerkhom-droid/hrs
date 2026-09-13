<?php

namespace App\Services\HR;

use App\Models\AttendanceRecord;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\AttendanceAutoCheckedOutNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class AttendanceAutoCheckOutService
{
    /** @var array<int, Collection> */
    private array $approverCache = [];


    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly OvertimeService $overtimeService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Process Due Records
    |--------------------------------------------------------------------------
    */

    public function processDueRecords(
        Carbon $now,
        ?int $tenantId = null,
        bool $dryRun = false
    ): array {
        $now =
            $now->copy()->utc();

        $summary = [
            'scanned' =>
                0,

            'due' =>
                0,

            'processed' =>
                0,

            'skipped' =>
                0,

            'failed' =>
                0,
        ];

        AttendanceRecord::withoutGlobalScopes()
            ->with([
                'shift.policy',
                'employee.user',
                'employee.manager.user',
            ])
            ->when(
                $tenantId,
                fn ($query) =>
                    $query->where(
                        'tenant_id',
                        $tenantId
                    )
            )
            ->whereNotNull(
                'check_in_at'
            )
            ->whereNull(
                'check_out_at'
            )
            ->whereNull(
                'attendance_records.deleted_at'
            )
            ->whereNotNull(
                'scheduled_check_out_at'
            )
            ->where(
                'scheduled_check_out_at',
                '<=',
                $now
            )
            ->orderBy(
                'id'
            )
            ->chunkById(
                100,
                function ($records) use (
                    $now,
                    $dryRun,
                    &$summary
                ) {
                    foreach (
                        $records
                        as $record
                    ) {
                        $summary['scanned']++;

                        if (
                            !$this->isDue(
                                $record,
                                $now
                            )
                        ) {
                            $summary['skipped']++;

                            continue;
                        }

                        $summary['due']++;

                        if ($dryRun) {
                            continue;
                        }

                        try {
                            $processed =
                                $this->processRecord(
                                    $record,
                                    $now
                                );

                            if (!$processed) {
                                $summary['skipped']++;

                                continue;
                            }

                            $summary['processed']++;

                            $this->sendNotifications(
                                $processed
                            );
                        } catch (Throwable $exception) {
                            $summary['failed']++;

                            Log::error(
                                'Attendance auto check-out failed.',
                                [
                                    'attendance_record_id' =>
                                        $record->id,

                                    'tenant_id' =>
                                        $record->tenant_id,

                                    'message' =>
                                        $exception
                                            ->getMessage(),
                                ]
                            );
                        }
                    }
                }
            );

        return $summary;
    }


    /*
    |--------------------------------------------------------------------------
    | Due Check
    |--------------------------------------------------------------------------
    */

    private function isDue(
        AttendanceRecord $record,
        Carbon $now
    ): bool {
        $policy =
            $record->shift?->policy;

        if (
            !$policy ||
            !$policy->is_active ||
            !$policy->auto_check_out ||
            !$record->scheduled_check_out_at
        ) {
            return false;
        }

        $dueAt =
            $record
                ->scheduled_check_out_at
                ->copy()
                ->addMinutes(
                    $policy
                        ->auto_check_out_after_minutes
                    ?? 60
                );

        return $now->gte(
            $dueAt
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Process One Record
    |--------------------------------------------------------------------------
    */

    private function processRecord(
        AttendanceRecord $record,
        Carbon $now
    ): ?AttendanceRecord {
        return DB::transaction(
            function () use (
                $record,
                $now
            ) {
                $record =
                    AttendanceRecord::withoutGlobalScopes()
                        ->with([
                            'shift.policy',
                            'employee.user',
                            'employee.manager.user',
                        ])
                        ->where(
                            'tenant_id',
                            $record->tenant_id
                        )
                        ->whereKey(
                            $record->id
                        )
                        ->whereNull(
                            'attendance_records.deleted_at'
                        )
                        ->lockForUpdate()
                        ->first();

                if (
                    !$record ||
                    !$record->check_in_at ||
                    $record->check_out_at ||
                    $record->approval_status ===
                        'approved' ||
                    !$this->isDue(
                        $record,
                        $now
                    )
                ) {
                    return null;
                }

                $tenant =
                    Tenant::withoutGlobalScopes()
                        ->findOrFail(
                            $record->tenant_id
                        );

                $attendanceDate =
                    $record
                        ->attendance_date
                        ->toDateString();

                /*
                 * يعيد اكتشاف العطلة من قاعدة البيانات
                 * بدل الاعتماد فقط على metadata.
                 */
                $context =
                    $this->attendanceService
                        ->resolvePunchContext(
                            tenant: $tenant,
                            employee: $record->employee,
                            attendanceDate:
                                $attendanceDate,
                            shiftId:
                                $record->work_shift_id,
                            locationId:
                                $record->work_location_id
                        );

                $policy =
                    $context['policy'];

                $isHoliday =
                    (bool) (
                        $context['is_holiday']
                        ?? false
                    );

                /*
                 * الانصراف التلقائي يسجل نهاية
                 * الوردية وليس وقت تنفيذ الأمر.
                 */
                $checkOutAt =
                    $record
                        ->scheduled_check_out_at
                        ->copy();

                if (
                    $checkOutAt->lte(
                        $record->check_in_at
                    )
                ) {
                    $checkOutAt =
                        $record
                            ->check_in_at
                            ->copy()
                            ->addMinute();
                }

                $metrics =
                    $this->attendanceService
                        ->calculatePunchMetrics(
                            requestedStatus:
                                'present',

                            checkIn:
                                $record->check_in_at,

                            checkOut:
                                $checkOutAt,

                            scheduledIn:
                                $record
                                    ->scheduled_check_in_at,

                            scheduledOut:
                                $record
                                    ->scheduled_check_out_at,

                            plannedBreakMinutes:
                                $context['shift']
                                    ->break_minutes,

                            policy:
                                $policy,

                            isHoliday:
                                $isHoliday
                        );

                $metadata =
                    $record->metadata
                    ?? [];

                $metadata[
                    'auto_check_out'
                ] = [
                    'processed_at' =>
                        $now
                            ->toIso8601String(),

                    'recorded_check_out_at' =>
                        $checkOutAt
                            ->toIso8601String(),

                    'delay_minutes' =>
                        $policy
                            ->auto_check_out_after_minutes
                        ?? 60,

                    'reason' =>
                        'missing_employee_check_out',
                ];

                /*
                 * حفظ بيانات العطلة داخل السجل
                 * لتوضيح سبب احتساب كامل العمل إضافيًا.
                 */
                $holiday =
                    $context['holiday']
                    ?? null;

                if ($holiday) {
                    $metadata['holiday'] = [
                        'id' =>
                            $holiday->id,

                        'code' =>
                            $holiday->code,

                        'name' =>
                            $holiday->name,

                        'type' =>
                            $holiday->type,

                        'worked' =>
                            true,
                    ];
                } else {
                    unset(
                        $metadata['holiday']
                    );
                }

                $reviewReasons = [
                    'auto_check_out',
                    'manual_or_system_check_out',
                ];

                if ($isHoliday) {
                    $reviewReasons[] =
                        'holiday_work';
                }

                $metadata['approval'] = [
                    'mode' =>
                        $policy->approval_mode
                        ?: 'manual',

                    'source' =>
                        'pending_review',

                    'evaluated_at' =>
                        $now
                            ->toIso8601String(),

                    'review_reasons' =>
                        $reviewReasons,
                ];

                $record->forceFill([
                    'check_out_at' =>
                        $checkOutAt,

                    'check_out_source' =>
                        'system',

                    'check_out_ip' =>
                        null,

                    'check_out_device' =>
                        'laravel-scheduler',

                    'status' =>
                        $metrics['status'],

                    'work_minutes' =>
                        $metrics[
                            'work_minutes'
                        ],

                    'break_minutes' =>
                        $metrics[
                            'break_minutes'
                        ],

                    'late_minutes' =>
                        $metrics[
                            'late_minutes'
                        ],

                    'early_leave_minutes' =>
                        $metrics[
                            'early_leave_minutes'
                        ],

                    'overtime_minutes' =>
                        $metrics[
                            'overtime_minutes'
                        ],

                    'approval_status' =>
                        'pending',

                    'approved_at' =>
                        null,

                    'approved_by' =>
                        null,

                    'metadata' =>
                        $metadata,
                ])->save();

                /*
                 * مزامنة السجل مع وحدة العمل الإضافي.
                 * هذا مهم خصوصًا إذا كان اليوم عطلة.
                 */
                $this->overtimeService
                    ->syncWithAttendance(
                        $record
                    );

                return $record
                    ->refresh()
                    ->load([
                        'shift.policy',
                        'employee.user',
                        'employee.manager.user',
                    ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    private function sendNotifications(
        AttendanceRecord $record
    ): void {
        try {
            $recipients = collect([
                $record
                    ->employee
                    ?->user,

                $record
                    ->employee
                    ?->manager
                    ?->user,
            ])->filter();

            $recipients =
                $recipients
                    ->merge(
                        $this
                            ->attendanceApprovers(
                                $record
                                    ->tenant_id
                            )
                    )
                    ->unique(
                        'id'
                    )
                    ->values();

            if (
                $recipients->isEmpty()
            ) {
                return;
            }

            Notification::send(
                $recipients,
                new AttendanceAutoCheckedOutNotification(
                    $record
                )
            );
        } catch (Throwable $exception) {
            Log::warning(
                'Auto check-out saved but notification failed.',
                [
                    'attendance_record_id' =>
                        $record->id,

                    'message' =>
                        $exception
                            ->getMessage(),
                ]
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Approvers
    |--------------------------------------------------------------------------
    */

    private function attendanceApprovers(
        int $tenantId
    ): Collection {
        if (
            array_key_exists(
                $tenantId,
                $this->approverCache
            )
        ) {
            return $this
                ->approverCache[
                    $tenantId
                ];
        }

        $previousTenantId =
            function_exists(
                'getPermissionsTeamId'
            )
                ? getPermissionsTeamId()
                : null;

        try {
            if (
                function_exists(
                    'setPermissionsTeamId'
                )
            ) {
                setPermissionsTeamId(
                    $tenantId
                );
            }

            return $this
                ->approverCache[
                    $tenantId
                ] = User::query()
                    ->where(
                        'tenant_id',
                        $tenantId
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->get()
                    ->filter(
                        fn (User $user) =>
                            $user->can(
                                'attendance.approve'
                            )
                    )
                    ->values();
        } finally {
            if (
                function_exists(
                    'setPermissionsTeamId'
                )
            ) {
                setPermissionsTeamId(
                    $previousTenantId
                );
            }
        }
    }
}