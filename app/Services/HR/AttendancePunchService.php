<?php

namespace App\Services\HR;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

class AttendancePunchService
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly MobileDeviceService $deviceService
    ) {
    }

    public function checkIn(
        Tenant $tenant,
        User $user,
        array $data,
        ?string $ip = null,
        string $source = 'mobile'
    ): AttendanceRecord {
        $employee = $this->deviceService->employeeForUser($tenant, $user);
        $this->ensureValidSource($source);

        $device = $source === 'mobile'
            ? $this->deviceService->activeDevice(
                $tenant,
                $user,
                $data['device_uuid'],
                $ip
            )
            : null;

        $current = $this->currentContext($tenant, $employee);
        $timezone = $current['timezone'];
        $attendanceDate = $current['date'];
        $context = $current['context'];

        $this->ensurePunchAllowed($context, $source);
        $this->ensureWorkDay($attendanceDate, $context['shift'], $timezone);

        $now = now()->utc();
        $this->ensureCheckInWindow(
            $now,
            $context['scheduled_in'],
            $context['scheduled_out'],
            $context['policy']->early_check_in_minutes,
            $context['policy']->late_check_out_minutes
        );

        $distance = $this->validateGeofence(
            $context['location'],
            $context['policy']->require_geofence,
            $context['policy']->allow_outside_geofence,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null
        );

        $this->ensurePhotoProvided(
            $context['policy']->require_photo,
            $data['photo'] ?? null
        );

        $photoPath = null;

        try {
            if (($data['photo'] ?? null) instanceof UploadedFile) {
                $photoPath = $this->storePhoto(
                    $tenant,
                    $employee,
                    $attendanceDate,
                    'check-in',
                    $data['photo']
                );
            }

            return DB::transaction(function () use (
                $tenant,
                $user,
                $employee,
                $device,
                $data,
                $ip,
                $attendanceDate,
                $context,
                $now,
                $distance,
                $photoPath,
                $source
            ) {
                $record = AttendanceRecord::query()
                    ->withTrashed()
                    ->where('tenant_id', $tenant->id)
                    ->where('employee_id', $employee->id)
                    ->whereDate('attendance_date', $attendanceDate)
                    ->lockForUpdate()
                    ->first();

                $reusedArchivedRecord = false;
                $archivedPhotoPaths = [];

                if ($record?->trashed()) {
                    $reusedArchivedRecord = true;
                    $archivedPhotoPaths = array_values(array_filter([
                        $record->check_in_photo_path,
                        $record->check_out_photo_path,
                    ]));

                    $record->restore();
                    $record->forceFill([
                        'check_out_at' => null,
                        'check_out_source' => null,
                        'check_out_latitude' => null,
                        'check_out_longitude' => null,
                        'check_out_distance' => null,
                        'check_out_ip' => null,
                        'check_out_device' => null,
                        'check_out_photo_path' => null,
                        'approved_at' => null,
                        'approved_by' => null,
                        'notes' => null,
                    ]);
                }

                if (!$reusedArchivedRecord && $record?->check_in_at) {
                    throw new LogicException(
                        'تم تسجيل الحضور مسبقًا لهذا اليوم.'
                    );
                }

                if (
                    !$reusedArchivedRecord
                    && $record?->approval_status === 'approved'
                ) {
                    throw new LogicException(
                        'سجل اليوم معتمد ولا يمكن تغييره من الخدمة الذاتية.'
                    );
                }

                if (
                    !$reusedArchivedRecord
                    && $record
                    && in_array($record->status, ['on_leave', 'holiday'], true)
                ) {
                    throw new LogicException(
                        'لا يمكن تسجيل الحضور لأن اليوم مسجل كإجازة أو عطلة.'
                    );
                }

                $metrics = $this->attendanceService->calculatePunchMetrics(
                    'present',
                    $now,
                    null,
                    $context['scheduled_in'],
                    $context['scheduled_out'],
                    $context['shift']->break_minutes,
                    $context['policy']
                );

                $metadata = array_filter([
                    ...($reusedArchivedRecord
                        ? [
                            'archived_record_reused_at' =>
                                now()->toIso8601String(),
                        ]
                        : ($record?->metadata ?? [])),
                    'check_in_accuracy' => $data['accuracy'] ?? null,
                    'mobile_device_id' => $device?->id,
                    'check_in_channel' => $source,
                ], fn ($value) => $value !== null);

                $record ??= new AttendanceRecord();
                $record->forceFill([
                    'tenant_id' => $tenant->id,
                    'employee_id' => $employee->id,
                    'work_shift_id' => $context['shift']->id,
                    'work_location_id' => $context['location']?->id,
                    'attendance_date' => $attendanceDate,
                    'timezone' => $context['policy']->timezone,
                    'scheduled_check_in_at' => $context['scheduled_in'],
                    'scheduled_check_out_at' => $context['scheduled_out'],
                    'check_in_at' => $now,
                    'check_in_source' => $source,
                    'check_in_latitude' => $data['latitude'] ?? null,
                    'check_in_longitude' => $data['longitude'] ?? null,
                    'check_in_distance' => $distance,
                    'check_in_ip' => $ip,
                    'check_in_device' => $device?->device_uuid
                        ?: ($data['device_name'] ?? 'web-browser'),
                    'check_in_photo_path' => $photoPath,
                    'status' => $metrics['status'],
                    'work_minutes' => $metrics['work_minutes'],
                    'break_minutes' => $metrics['break_minutes'],
                    'late_minutes' => $metrics['late_minutes'],
                    'early_leave_minutes' => $metrics['early_leave_minutes'],
                    'overtime_minutes' => $metrics['overtime_minutes'],
                    'approval_status' => 'pending',
                    'created_by' => $user->id,
                    'metadata' => $metadata,
                ])->save();

                if ($archivedPhotoPaths !== []) {
                    DB::afterCommit(
                        fn () => Storage::disk('local')
                            ->delete($archivedPhotoPaths)
                    );
                }

                return $this->loadRecord($record);
            });
        } catch (QueryException $exception) {
            if ($photoPath) {
                Storage::disk('local')->delete($photoPath);
            }

            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw new LogicException(
                    'يوجد تسجيل حضور لهذا الموظف في تاريخ اليوم. حدّث الصفحة ولا تضغط أكثر من مرة.'
                );
            }

            throw $exception;
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::disk('local')->delete($photoPath);
            }

            throw $exception;
        }
    }

    public function checkOut(
        Tenant $tenant,
        User $user,
        array $data,
        ?string $ip = null,
        string $source = 'mobile'
    ): AttendanceRecord {
        $employee = $this->deviceService->employeeForUser($tenant, $user);
        $this->ensureValidSource($source);

        $device = $source === 'mobile'
            ? $this->deviceService->activeDevice(
                $tenant,
                $user,
                $data['device_uuid'],
                $ip
            )
            : null;

        $timezone = $employee->timezone
            ?: $tenant->timezone
            ?: 'Asia/Riyadh';
        $now = now()->utc();

        $record = AttendanceRecord::query()
            ->where('tenant_id', $tenant->id)
            ->where('employee_id', $employee->id)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->latest('attendance_date')
            ->first();

        if (!$record) {
            throw new LogicException(
                'لا يوجد تسجيل حضور مفتوح لإنهائه.'
            );
        }

        $attendanceDate = $record->attendance_date->toDateString();
        $context = $this->attendanceService->resolvePunchContext(
            $tenant,
            $employee,
            $attendanceDate,
            $record->work_shift_id,
            $record->work_location_id
        );

        $this->ensurePunchAllowed($context, $source);

        if ($now->lte($record->check_in_at)) {
            throw new LogicException(
                'وقت الانصراف يجب أن يكون بعد وقت الحضور.'
            );
        }

        $distance = $this->validateGeofence(
            $context['location'],
            $context['policy']->require_geofence,
            $context['policy']->allow_outside_geofence,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null
        );

        $this->ensurePhotoProvided(
            $context['policy']->require_photo,
            $data['photo'] ?? null
        );

        $photoPath = null;

        try {
            if (($data['photo'] ?? null) instanceof UploadedFile) {
                $photoPath = $this->storePhoto(
                    $tenant,
                    $employee,
                    $attendanceDate,
                    'check-out',
                    $data['photo']
                );
            }

            return DB::transaction(function () use (
                $record,
                $tenant,
                $employee,
                $device,
                $data,
                $ip,
                $context,
                $now,
                $distance,
                $photoPath,
                $source
            ) {
                $record = AttendanceRecord::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('employee_id', $employee->id)
                    ->whereKey($record->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($record->check_out_at) {
                    throw new LogicException(
                        'تم تسجيل الانصراف مسبقًا.'
                    );
                }

                if ($record->approval_status === 'approved') {
                    throw new LogicException(
                        'سجل اليوم معتمد ولا يمكن تغييره من الخدمة الذاتية.'
                    );
                }

                $metrics = $this->attendanceService->calculatePunchMetrics(
                    'present',
                    $record->check_in_at,
                    $now,
                    $record->scheduled_check_in_at,
                    $record->scheduled_check_out_at,
                    $context['shift']->break_minutes,
                    $context['policy']
                );

                $metadata = $record->metadata ?? [];
                $metadata['check_out_accuracy'] = $data['accuracy'] ?? null;
                $metadata['check_out_channel'] = $source;

                $record->forceFill([
                    'check_out_at' => $now,
                    'check_out_source' => $source,
                    'check_out_latitude' => $data['latitude'] ?? null,
                    'check_out_longitude' => $data['longitude'] ?? null,
                    'check_out_distance' => $distance,
                    'check_out_ip' => $ip,
                    'check_out_device' => $device?->device_uuid
                        ?: ($data['device_name'] ?? 'web-browser'),
                    'check_out_photo_path' => $photoPath,
                    'status' => $metrics['status'],
                    'work_minutes' => $metrics['work_minutes'],
                    'break_minutes' => $metrics['break_minutes'],
                    'late_minutes' => $metrics['late_minutes'],
                    'early_leave_minutes' => $metrics['early_leave_minutes'],
                    'overtime_minutes' => $metrics['overtime_minutes'],
                    'approval_status' => 'pending',
                    'metadata' => $metadata,
                ])->save();

                return $this->loadRecord($record);
            });
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::disk('local')->delete($photoPath);
            }

            throw $exception;
        }
    }

    public function today(
        Tenant $tenant,
        User $user,
        string $source = 'mobile'
    ): array {
        $this->ensureValidSource($source);
        $employee = $this->deviceService->employeeForUser($tenant, $user);
        $current = $this->currentContext($tenant, $employee);
        $timezone = $current['timezone'];
        $attendanceDate = $current['date'];
        $context = $current['context'];

        $record = AttendanceRecord::query()
            ->where('tenant_id', $tenant->id)
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $attendanceDate)
            ->first();

        $isWorkDay = $this->isWorkDay(
            $attendanceDate,
            $context['shift'],
            $timezone
        );
        $channelAllowed = $this->channelAllowed($context, $source);
        $insideWindow = $this->insideCheckInWindow(
            now()->utc(),
            $context['scheduled_in'],
            $context['scheduled_out'],
            $context['policy']->early_check_in_minutes,
            $context['policy']->late_check_out_minutes
        );

        return [
            'date' => $attendanceDate,
            'timezone' => $timezone,
            'is_work_day' => $isWorkDay,
            'can_check_in' => $channelAllowed
                && $isWorkDay
                && $insideWindow
                && !$record?->check_in_at,
            'can_check_out' => $channelAllowed
                && $record?->check_in_at
                && !$record?->check_out_at,
            'channel_allowed' => $channelAllowed,
            // Backward-compatible key for the first Flutter API package.
            'mobile_allowed' => $source === 'mobile'
                ? $channelAllowed
                : (bool) $context['policy']->allow_mobile,
            'source' => $source,
            'inside_check_in_window' => $insideWindow,
            'shift' => $context['shift'],
            'policy' => $context['policy'],
            'work_location' => $context['location'],
            'record' => $record ? $this->loadRecord($record) : null,
        ];
    }

    public function history(
        Tenant $tenant,
        User $user,
        int $perPage = 15
    ): LengthAwarePaginator {
        $employee = $this->deviceService->employeeForUser($tenant, $user);

        return AttendanceRecord::query()
            ->with([
                'shift:id,uuid,tenant_id,code,name,start_time,end_time,crosses_midnight,work_days',
                'workLocation:id,tenant_id,code,name,latitude,longitude,attendance_radius',
            ])
            ->where('tenant_id', $tenant->id)
            ->where('employee_id', $employee->id)
            ->latest('attendance_date')
            ->paginate(min(max($perPage, 10), 50));
    }

    private function ensurePunchAllowed(
        array $context,
        string $source
    ): void {
        if (!$context['shift'] || !$context['shift']->is_active) {
            throw new LogicException(
                'لم يتم تعيين وردية عمل نشطة للموظف.'
            );
        }

        if (!$this->channelAllowed($context, $source)) {
            throw new LogicException(
                $source === 'web'
                    ? 'تسجيل الحضور من الويب غير مسموح حسب سياسة الشركة.'
                    : 'تسجيل الحضور من تطبيق الجوال غير مسموح حسب سياسة الشركة.'
            );
        }
    }

    private function channelAllowed(
        array $context,
        string $source
    ): bool {
        if (!$context['shift']?->is_active || !$context['policy']->is_active) {
            return false;
        }

        return $source === 'web'
            ? (bool) $context['policy']->allow_web
            : (bool) $context['policy']->allow_mobile;
    }

    private function ensureValidSource(string $source): void
    {
        if (!in_array($source, ['web', 'mobile'], true)) {
            throw new LogicException('مصدر تسجيل الحضور غير مدعوم.');
        }
    }

    private function ensureWorkDay(
        string $date,
        mixed $shift,
        string $timezone
    ): void {
        if (!$this->isWorkDay($date, $shift, $timezone)) {
            throw new LogicException(
                'اليوم ليس من أيام عمل الوردية المحددة.'
            );
        }
    }

    private function isWorkDay(
        string $date,
        mixed $shift,
        string $timezone
    ): bool {
        if (!$shift) {
            return false;
        }

        $day = Carbon::parse($date, $timezone)->dayOfWeek;

        return in_array($day, $shift->work_days ?? [], true);
    }

    private function ensureCheckInWindow(
        Carbon $now,
        ?Carbon $scheduledIn,
        ?Carbon $scheduledOut,
        int $earlyMinutes,
        int $lateMinutes
    ): void {
        if (!$scheduledIn || !$scheduledOut) {
            throw new LogicException(
                'تعذر تحديد وقت الوردية لهذا اليوم.'
            );
        }

        $opensAt = $scheduledIn->copy()->subMinutes($earlyMinutes);
        $closesAt = $scheduledOut->copy()->addMinutes($lateMinutes);

        if ($now->lt($opensAt)) {
            throw new LogicException(
                'لم يبدأ وقت السماح بتسجيل الحضور بعد.'
            );
        }

        if ($now->gt($closesAt)) {
            throw new LogicException(
                'انتهت نافذة تسجيل الحضور لهذه الوردية.'
            );
        }
    }

    private function insideCheckInWindow(
        Carbon $now,
        ?Carbon $scheduledIn,
        ?Carbon $scheduledOut,
        int $earlyMinutes,
        int $lateMinutes
    ): bool {
        if (!$scheduledIn || !$scheduledOut) {
            return false;
        }

        return $now->betweenIncluded(
            $scheduledIn->copy()->subMinutes($earlyMinutes),
            $scheduledOut->copy()->addMinutes($lateMinutes)
        );
    }

    private function currentContext(
        Tenant $tenant,
        Employee $employee
    ): array {
        $timezone = $employee->timezone
            ?: $tenant->timezone
            ?: 'Asia/Riyadh';
        $date = now($timezone)->toDateString();
        $context = $this->attendanceService->resolvePunchContext(
            $tenant,
            $employee,
            $date
        );

        $policyTimezone = $context['policy']->timezone ?: $timezone;
        $policyDate = now($policyTimezone)->toDateString();

        if ($policyDate !== $date) {
            $date = $policyDate;
            $context = $this->attendanceService->resolvePunchContext(
                $tenant,
                $employee,
                $date
            );
            $policyTimezone = $context['policy']->timezone ?: $policyTimezone;
        }

        return [
            'date' => $date,
            'timezone' => $policyTimezone,
            'context' => $context,
        ];
    }

    private function validateGeofence(
        ?WorkLocation $location,
        bool $required,
        bool $allowOutside,
        mixed $latitude,
        mixed $longitude
    ): ?int {
        if (!$location || !$location->hasCoordinates()) {
            if ($required) {
                throw new LogicException(
                    'موقع عمل الموظف لا يحتوي على إحداثيات جغرافية.'
                );
            }

            return null;
        }

        if ($latitude === null || $longitude === null) {
            if ($required) {
                throw new LogicException(
                    'يجب السماح للتطبيق بقراءة الموقع الجغرافي.'
                );
            }

            return null;
        }

        $distance = $this->distanceInMeters(
            (float) $latitude,
            (float) $longitude,
            (float) $location->latitude,
            (float) $location->longitude
        );

        if (
            $required
            && !$allowOutside
            && $distance > $location->attendance_radius
        ) {
            throw new LogicException(
                'أنت خارج نطاق موقع العمل المسموح. المسافة الحالية '
                . $distance
                . ' متر.'
            );
        }

        return $distance;
    }

    private function distanceInMeters(
        float $latitude1,
        float $longitude1,
        float $latitude2,
        float $longitude2
    ): int {
        $earthRadius = 6371000;
        $latitudeDelta = deg2rad($latitude2 - $latitude1);
        $longitudeDelta = deg2rad($longitude2 - $longitude1);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($latitude1))
            * cos(deg2rad($latitude2))
            * sin($longitudeDelta / 2) ** 2;

        return (int) round(
            $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a))
        );
    }

    private function ensurePhotoProvided(
        bool $required,
        mixed $photo
    ): void {
        if ($required && !$photo instanceof UploadedFile) {
            throw new LogicException(
                'صورة إثبات الحضور مطلوبة حسب سياسة الشركة.'
            );
        }
    }

    private function storePhoto(
        Tenant $tenant,
        Employee $employee,
        string $date,
        string $type,
        UploadedFile $photo
    ): string {
        $extension = strtolower(
            $photo->getClientOriginalExtension() ?: 'jpg'
        );
        $path = 'tenants/'
            . $tenant->id
            . '/employees/'
            . $employee->uuid
            . '/attendance/'
            . $date;

        return $photo->storeAs(
            $path,
            $type . '-' . Str::uuid() . '.' . $extension,
            'local'
        );
    }

    private function loadRecord(
        AttendanceRecord $record
    ): AttendanceRecord {
        return $record->load([
            'shift:id,uuid,tenant_id,code,name,start_time,end_time,crosses_midnight,work_days',
            'workLocation:id,tenant_id,code,name,latitude,longitude,attendance_radius',
        ]);
    }
}