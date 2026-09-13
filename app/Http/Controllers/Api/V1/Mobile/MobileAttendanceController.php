<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MobileAttendancePunchRequest;
use App\Models\AttendanceRecord;
use App\Models\Tenant;
use App\Services\HR\AttendancePunchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

class MobileAttendanceController extends Controller
{
    public function __construct(
        private readonly AttendancePunchService $punchService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | بيانات حضور اليوم
    |--------------------------------------------------------------------------
    */

    public function today(Request $request): JsonResponse
    {
        $this->authorizeAttendance($request);

        try {
            $data = $this->punchService->today(
                $this->currentTenant($request),
                $request->user(),
                'mobile'
            );

            return response()->json([
                'success' => true,

                'date' => $data['date'],

                'timezone' => $data['timezone'],

                'is_work_day' => (bool) $data['is_work_day'],

                'is_holiday' => (bool) (
                    $data['is_holiday'] ?? false
                ),

                'holiday' => $data['holiday'] ?? null,

                'mobile_allowed' => (bool) $data['channel_allowed'],

                'inside_check_in_window' => (bool) (
                    $data['inside_check_in_window'] ?? false
                ),

                'can_check_in' => (bool) $data['can_check_in'],

                'can_check_out' => (bool) $data['can_check_out'],

                /*
                 * هذه القيم يحتاجها Flutter ليعرف
                 * هل يطلب الموقع والصورة أم لا.
                 */
                'require_photo' => (bool) (
                    $data['policy']?->require_photo ?? false
                ),

                'require_geofence' => (bool) (
                    $data['policy']?->require_geofence ?? false
                ),

                'allow_outside_geofence' => (bool) (
                    $data['policy']?->allow_outside_geofence ?? false
                ),

                'max_location_accuracy' => (int) (
                    $data['policy']?->max_location_accuracy ?? 100
                ),

                'shift' => $this->shiftPayload(
                    $data['shift']
                ),

                'policy' => $this->policyPayload(
                    $data['policy']
                ),

                'work_location' => $this->workLocationPayload(
                    $data['work_location']
                ),

                'attendance' => $data['record']
                    ? $this->recordPayload($data['record'])
                    : null,
            ]);
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تسجيل الحضور
    |--------------------------------------------------------------------------
    */

    public function checkIn(
        MobileAttendancePunchRequest $request
    ): JsonResponse {
        try {
            $record = $this->punchService->checkIn(
                $this->currentTenant($request),
                $request->user(),
                $request->validated(),
                $request->ip(),
                'mobile'
            );

            return response()->json([
                'success' => true,

                'message' => 'تم تسجيل الحضور بنجاح.',

                'attendance' => $this->recordPayload(
                    $record
                ),
            ], 201);
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تسجيل الانصراف
    |--------------------------------------------------------------------------
    */

    public function checkOut(
        MobileAttendancePunchRequest $request
    ): JsonResponse {
        try {
            $record = $this->punchService->checkOut(
                $this->currentTenant($request),
                $request->user(),
                $request->validated(),
                $request->ip(),
                'mobile'
            );

            return response()->json([
                'success' => true,

                'message' => 'تم تسجيل الانصراف بنجاح.',

                'attendance' => $this->recordPayload(
                    $record
                ),
            ]);
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | سجل حضور الموظف
    |--------------------------------------------------------------------------
    */

    public function history(
        Request $request
    ): JsonResponse {
        $this->authorizeAttendance($request);

        $validated = $request->validate([
            'per_page' => [
                'nullable',
                'integer',
                'between:10,50',
            ],
        ]);

        try {
            $records = $this->punchService->history(
                $this->currentTenant($request),
                $request->user(),
                (int) ($validated['per_page'] ?? 15)
            );

            return response()->json([
                'success' => true,

                'data' => array_map(
                    fn (AttendanceRecord $record) =>
                        $this->recordPayload($record),
                    $records->items()
                ),

                'meta' => [
                    'current_page' =>
                        $records->currentPage(),

                    'last_page' =>
                        $records->lastPage(),

                    'per_page' =>
                        $records->perPage(),

                    'total' =>
                        $records->total(),

                    'has_more_pages' =>
                        $records->hasMorePages(),
                ],
            ]);
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | بيانات سجل الحضور
    |--------------------------------------------------------------------------
    */

    private function recordPayload(
        AttendanceRecord $record
    ): array {
        $record->loadMissing([
            'shift',
            'workLocation',
        ]);

        return [
            'uuid' => $record->uuid,

            'attendance_date' =>
                $record->attendance_date?->toDateString(),

            'timezone' => $record->timezone,

            'scheduled_check_in_at' =>
                $record->scheduled_check_in_at
                    ?->toIso8601String(),

            'scheduled_check_out_at' =>
                $record->scheduled_check_out_at
                    ?->toIso8601String(),

            'check_in_at' =>
                $record->check_in_at?->toIso8601String(),

            'check_out_at' =>
                $record->check_out_at?->toIso8601String(),

            'check_in_source' =>
                $record->check_in_source,

            'check_out_source' =>
                $record->check_out_source,

            'check_in_latitude' =>
                $record->check_in_latitude,

            'check_in_longitude' =>
                $record->check_in_longitude,

            'check_in_distance' =>
                $record->check_in_distance,

            'check_out_latitude' =>
                $record->check_out_latitude,

            'check_out_longitude' =>
                $record->check_out_longitude,

            'check_out_distance' =>
                $record->check_out_distance,

            'has_check_in_photo' =>
                filled($record->check_in_photo_path),

            'has_check_out_photo' =>
                filled($record->check_out_photo_path),

            'status' => $record->status,

            'status_label' =>
                $record->status_label,

            'work_minutes' =>
                (int) $record->work_minutes,

            'work_duration_label' =>
                $record->work_duration_label,

            'break_minutes' =>
                (int) $record->break_minutes,

            'late_minutes' =>
                (int) $record->late_minutes,

            'early_leave_minutes' =>
                (int) $record->early_leave_minutes,

            'overtime_minutes' =>
                (int) $record->overtime_minutes,

            'approval_status' =>
                $record->approval_status,

            'approval_status_label' =>
                $record->approval_status_label,

            'shift' => $this->shiftPayload(
                $record->shift
            ),

            'work_location' =>
                $record->workLocation
                    ? [
                        'id' =>
                            $record->workLocation->id,

                        'code' =>
                            $record->workLocation->code,

                        'name' =>
                            $record->workLocation->name,
                    ]
                    : null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | بيانات الوردية
    |--------------------------------------------------------------------------
    */

    private function shiftPayload(
        mixed $shift
    ): ?array {
        if (!$shift) {
            return null;
        }

        return [
            'id' => $shift->id,

            'code' => $shift->code,

            'name' => $shift->name,

            'start_time' => substr(
                (string) $shift->start_time,
                0,
                5
            ),

            'end_time' => substr(
                (string) $shift->end_time,
                0,
                5
            ),

            'crosses_midnight' =>
                (bool) $shift->crosses_midnight,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | بيانات سياسة الحضور
    |--------------------------------------------------------------------------
    */

    private function policyPayload(
        mixed $policy
    ): ?array {
        if (!$policy) {
            return null;
        }

        return [
            'id' => $policy->id,

            'name' => $policy->name,

            'timezone' => $policy->timezone,

            'allow_mobile' =>
                (bool) $policy->allow_mobile,

            'require_geofence' =>
                (bool) $policy->require_geofence,

            'allow_outside_geofence' =>
                (bool) $policy->allow_outside_geofence,

            'require_photo' =>
                (bool) $policy->require_photo,

            'max_location_accuracy' =>
                (int) (
                    $policy->max_location_accuracy
                    ?? 100
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | بيانات موقع العمل
    |--------------------------------------------------------------------------
    */

    private function workLocationPayload(
        mixed $location
    ): ?array {
        if (!$location) {
            return null;
        }

        return [
            'id' => $location->id,

            'code' => $location->code,

            'name' => $location->name,

            'latitude' => $location->latitude,

            'longitude' => $location->longitude,

            'attendance_radius' =>
                (int) $location->attendance_radius,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | التحقق من الصلاحية
    |--------------------------------------------------------------------------
    */

    private function authorizeAttendance(
        Request $request
    ): void {
        abort_unless(
            $request->user()?->can(
                'self_service.attendance'
            ),
            403,
            'لا تملك صلاحية تسجيل الحضور.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | الشركة الحالية
    |--------------------------------------------------------------------------
    */

    private function currentTenant(
        Request $request
    ): Tenant {
        $tenantId = $request->user()?->tenant_id;

        abort_unless(
            $tenantId,
            403,
            'هذا الحساب غير مرتبط بشركة.'
        );

        return Tenant::query()->findOrFail(
            $tenantId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | أخطاء قواعد الحضور
    |--------------------------------------------------------------------------
    */

    private function logicError(
        LogicException $exception
    ): JsonResponse {
        return response()->json([
            'success' => false,

            'message' =>
                $exception->getMessage(),
        ], 422);
    }
}