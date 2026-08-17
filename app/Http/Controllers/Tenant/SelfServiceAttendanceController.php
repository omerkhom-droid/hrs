<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\WebAttendancePunchRequest;
use App\Models\AttendanceRecord;
use App\Models\Tenant;
use App\Services\HR\AttendancePunchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use LogicException;

class SelfServiceAttendanceController extends Controller
{
    public function __construct(
        private readonly AttendancePunchService $punchService
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorizeSelfService($request);

        return view('tenant.attendance.self-service');
    }

    public function today(Request $request): JsonResponse
    {
        $this->authorizeSelfService($request);

        try {
            $data = $this->punchService->today(
                $this->currentTenant($request),
                $request->user(),
                'web'
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'data' => $this->todayPayload($data),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $this->authorizeSelfService($request);
        $request->validate([
            'per_page' => ['nullable', 'integer', 'between:10,50'],
        ]);

        try {
            $records = $this->punchService->history(
                $this->currentTenant($request),
                $request->user(),
                $request->integer('per_page', 10)
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'data' => array_map(
                fn (AttendanceRecord $record) =>
                    $this->recordPayload($record),
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
        WebAttendancePunchRequest $request
    ): JsonResponse {
        try {
            $record = $this->punchService->checkIn(
                $this->currentTenant($request),
                $request->user(),
                $this->punchData($request),
                $request->ip(),
                'web'
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الحضور بنجاح.',
            'record' => $this->recordPayload($record),
        ], 201);
    }

    public function checkOut(
        WebAttendancePunchRequest $request
    ): JsonResponse {
        try {
            $record = $this->punchService->checkOut(
                $this->currentTenant($request),
                $request->user(),
                $this->punchData($request),
                $request->ip(),
                'web'
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الانصراف بنجاح.',
            'record' => $this->recordPayload($record),
        ]);
    }

    private function punchData(
        WebAttendancePunchRequest $request
    ): array {
        return [
            ...$request->validated(),
            'device_name' => Str::limit(
                (string) $request->userAgent(),
                250,
                ''
            ),
        ];
    }

    private function todayPayload(array $data): array
    {
        $shift = $data['shift'];
        $policy = $data['policy'];
        $location = $data['work_location'];

        return [
            'date' => $data['date'],
            'timezone' => $data['timezone'],
            'is_work_day' => (bool) $data['is_work_day'],
            'web_allowed' => (bool) $data['channel_allowed'],
            'inside_check_in_window' =>
                (bool) $data['inside_check_in_window'],
            'can_check_in' => (bool) $data['can_check_in'],
            'can_check_out' => (bool) $data['can_check_out'],
            'shift' => $shift ? [
                'id' => $shift->id,
                'code' => $shift->code,
                'name' => $shift->name,
                'start_time' => substr((string) $shift->start_time, 0, 5),
                'end_time' => substr((string) $shift->end_time, 0, 5),
                'crosses_midnight' => (bool) $shift->crosses_midnight,
            ] : null,
            'policy' => [
                'require_geofence' => (bool) $policy->require_geofence,
                'allow_outside_geofence' =>
                    (bool) $policy->allow_outside_geofence,
                'require_photo' => (bool) $policy->require_photo,
            ],
            'work_location' => $location ? [
                'id' => $location->id,
                'code' => $location->code,
                'name' => $location->name,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'attendance_radius' => $location->attendance_radius,
            ] : null,
            'record' => $data['record']
                ? $this->recordPayload($data['record'])
                : null,
        ];
    }

    private function recordPayload(AttendanceRecord $record): array
    {
        return [
            'uuid' => $record->uuid,
            'attendance_date' => $record->attendance_date?->toDateString(),
            'timezone' => $record->timezone,
            'check_in_at' => $record->check_in_at?->toIso8601String(),
            'check_out_at' => $record->check_out_at?->toIso8601String(),
            'status' => $record->status,
            'status_label' => $record->status_label,
            'work_minutes' => $record->work_minutes,
            'work_duration_label' => $record->work_duration_label,
            'late_minutes' => $record->late_minutes,
            'early_leave_minutes' => $record->early_leave_minutes,
            'overtime_minutes' => $record->overtime_minutes,
            'approval_status' => $record->approval_status,
            'approval_status_label' => $record->approval_status_label,
            'check_in_distance' => $record->check_in_distance,
            'check_out_distance' => $record->check_out_distance,
            'shift' => $record->shift ? [
                'name' => $record->shift->name,
                'start_time' => substr(
                    (string) $record->shift->start_time,
                    0,
                    5
                ),
                'end_time' => substr(
                    (string) $record->shift->end_time,
                    0,
                    5
                ),
            ] : null,
            'work_location' => $record->workLocation ? [
                'name' => $record->workLocation->name,
            ] : null,
        ];
    }

    private function authorizeSelfService(Request $request): void
    {
        abort_unless(
            $request->user()?->can('self_service.attendance'),
            403,
            'لا تملك صلاحية تسجيل الحضور.'
        );
    }

    private function currentTenant(Request $request): Tenant
    {
        return Tenant::query()->findOrFail(
            $request->user()->tenant_id
        );
    }

    private function logicError(
        LogicException $exception
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
        ], 422);
    }
}
