<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AttendancePunchRequest;
use App\Models\AttendanceRecord;
use App\Models\Tenant;
use App\Services\HR\AttendancePunchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendancePunchService $punchService
    ) {
    }

    public function today(Request $request): JsonResponse
    {
        $this->ensurePermission($request);
        $tenant = Tenant::query()->findOrFail($request->user()->tenant_id);

        try {
            $data = $this->punchService->today($tenant, $request->user());
        } catch (LogicException $exception) {
            return $this->businessError($exception);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $data['date'],
                'timezone' => $data['timezone'],
                'is_work_day' => $data['is_work_day'],
                'mobile_allowed' => $data['channel_allowed'],
                'inside_check_in_window' => $data['inside_check_in_window'],
                'can_check_in' => (bool) $data['can_check_in'],
                'can_check_out' => (bool) $data['can_check_out'],
                'shift' => $this->shiftData($data['shift']),
                'policy' => $this->policyData($data['policy']),
                'work_location' => $this->locationData($data['work_location']),
                'record' => $data['record']
                    ? $this->recordData($data['record'])
                    : null,
            ],
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $this->ensurePermission($request);
        $request->validate([
            'per_page' => ['nullable', 'integer', 'between:10,50'],
        ]);

        $tenant = Tenant::query()->findOrFail($request->user()->tenant_id);
        $records = $this->punchService->history(
            $tenant,
            $request->user(),
            (int) $request->input('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'data' => array_map(
                fn (AttendanceRecord $record) => $this->recordData($record),
                $records->items()
            ),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    public function checkIn(
        AttendancePunchRequest $request
    ): JsonResponse {
        $this->ensurePermission($request);
        $tenant = Tenant::query()->findOrFail($request->user()->tenant_id);

        try {
            $record = $this->punchService->checkIn(
                $tenant,
                $request->user(),
                $request->validated(),
                $request->ip()
            );
        } catch (LogicException $exception) {
            return $this->businessError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الحضور بنجاح.',
            'data' => $this->recordData($record),
        ], 201);
    }

    public function checkOut(
        AttendancePunchRequest $request
    ): JsonResponse {
        $this->ensurePermission($request);
        $tenant = Tenant::query()->findOrFail($request->user()->tenant_id);

        try {
            $record = $this->punchService->checkOut(
                $tenant,
                $request->user(),
                $request->validated(),
                $request->ip()
            );
        } catch (LogicException $exception) {
            return $this->businessError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الانصراف بنجاح.',
            'data' => $this->recordData($record),
        ]);
    }

    private function ensurePermission(Request $request): void
    {
        abort_unless(
            $request->user()?->tokenCan('attendance:self-service')
            && $request->user()?->can('self_service.attendance'),
            403,
            'لا تملك صلاحية الخدمة الذاتية للحضور.'
        );
    }

    private function businessError(LogicException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
        ], 422);
    }

    private function recordData(AttendanceRecord $record): array
    {
        return [
            'uuid' => $record->uuid,
            'attendance_date' => $record->attendance_date?->toDateString(),
            'timezone' => $record->timezone,
            'scheduled_check_in_at' => $this->dateTime($record->scheduled_check_in_at),
            'scheduled_check_out_at' => $this->dateTime($record->scheduled_check_out_at),
            'check_in_at' => $this->dateTime($record->check_in_at),
            'check_out_at' => $this->dateTime($record->check_out_at),
            'status' => $record->status,
            'status_label' => $record->status_label,
            'work_minutes' => $record->work_minutes,
            'late_minutes' => $record->late_minutes,
            'early_leave_minutes' => $record->early_leave_minutes,
            'overtime_minutes' => $record->overtime_minutes,
            'approval_status' => $record->approval_status,
            'approval_status_label' => $record->approval_status_label,
            'check_in_distance' => $record->check_in_distance,
            'check_out_distance' => $record->check_out_distance,
            'shift' => $this->shiftData($record->shift),
            'work_location' => $this->locationData($record->workLocation),
        ];
    }

    private function shiftData(mixed $shift): ?array
    {
        if (!$shift) {
            return null;
        }

        return [
            'uuid' => $shift->uuid,
            'code' => $shift->code,
            'name' => $shift->name,
            'start_time' => substr((string) $shift->start_time, 0, 5),
            'end_time' => substr((string) $shift->end_time, 0, 5),
            'crosses_midnight' => (bool) $shift->crosses_midnight,
            'work_days' => $shift->work_days,
        ];
    }

    private function policyData(mixed $policy): ?array
    {
        if (!$policy) {
            return null;
        }

        return [
            'timezone' => $policy->timezone,
            'allow_mobile' => (bool) $policy->allow_mobile,
            'require_geofence' => (bool) $policy->require_geofence,
            'allow_outside_geofence' => (bool) $policy->allow_outside_geofence,
            'require_photo' => (bool) $policy->require_photo,
        ];
    }

    private function locationData(mixed $location): ?array
    {
        if (!$location) {
            return null;
        }

        return [
            'id' => $location->id,
            'code' => $location->code,
            'name' => $location->name,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'attendance_radius' => $location->attendance_radius,
        ];
    }

    private function dateTime(mixed $value): ?string
    {
        return $value?->copy()
            ->utc()
            ->toIso8601String();
    }
}
