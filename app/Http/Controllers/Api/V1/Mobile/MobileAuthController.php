<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MobileLoginRequest;
use App\Models\Employee;
use App\Models\User;
use App\Services\HR\MobileDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use LogicException;
use Throwable;

class MobileAuthController extends Controller
{
    public function __construct(
        private readonly MobileDeviceService $deviceService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | تسجيل الدخول
    |--------------------------------------------------------------------------
    */

    public function login(
        MobileLoginRequest $request
    ): JsonResponse {
        $data = $request->validated();

        $user = User::query()
            ->where('email', $data['email'])
            ->first();

        if (
            !$user
            || !Hash::check(
                $data['password'],
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'email' =>
                    'بيانات تسجيل الدخول غير صحيحة.',
            ]);
        }

        if (
            !$user->is_active
            || $user->is_system_admin
            || !$user->tenant_id
        ) {
            throw ValidationException::withMessages([
                'email' =>
                    'هذا الحساب غير متاح لتطبيق الموظفين.',
            ]);
        }

        $user->loadMissing('tenant');

        if (!$user->tenant) {
            throw ValidationException::withMessages([
                'email' =>
                    'الشركة المرتبطة بالحساب غير موجودة.',
            ]);
        }

        try {
            return DB::transaction(function () use (
                $request,
                $data,
                $user
            ) {
                $device = $this->deviceService->register(
                    $user->tenant,
                    $user,
                    [
                        'device_uuid' =>
                            $data['device_uuid'],

                        'platform' =>
                            $data['platform'],

                        'device_name' =>
                            $data['device_name'] ?? null,

                        'device_model' =>
                            $data['device_model'] ?? null,

                        'os_version' =>
                            $data['os_version'] ?? null,

                        'app_version' =>
                            $data['app_version'] ?? null,

                        'push_token' =>
                            $data['push_token'] ?? null,
                    ],
                    $request->ip()
                );

                /*
                 * Token واحد فعال لنفس المستخدم على نفس الجهاز.
                 */
                $tokenName = $this->tokenName(
                    $data['device_uuid']
                );

                $user->tokens()
                    ->where('name', $tokenName)
                    ->delete();

                $expiresAt = now()->addDays(90);

                $token = $user->createToken(
                    $tokenName,
                    [
                        'mobile',
                        'self-service',
                    ],
                    $expiresAt
                );

                $user->forceFill([
                    'last_login_at' => now(),
                ])->save();

                $employee =
                    $this->deviceService->employeeForUser(
                        $user->tenant,
                        $user
                    );

                return response()->json([
                    'success' => true,

                    'message' =>
                        'تم تسجيل الدخول بنجاح.',

                    'token_type' =>
                        'Bearer',

                    'access_token' =>
                        $token->plainTextToken,

                    'expires_at' =>
                        $expiresAt->toIso8601String(),

                    'user' =>
                        $this->userPayload($user),

                    'employee' =>
                        $this->employeePayload($employee),

                    'device' => [
                        'id' =>
                            $device->id,

                        'uuid' =>
                            $device->device_uuid,

                        'platform' =>
                            $device->platform,

                        'is_active' =>
                            (bool) $device->is_active,
                    ],
                ]);
            });
        } catch (LogicException $exception) {
            throw ValidationException::withMessages([
                'email' =>
                    $exception->getMessage(),
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | المستخدم الحالي
    |--------------------------------------------------------------------------
    */

    public function me(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        $user->loadMissing('tenant');

        try {
            $employee =
                $this->deviceService->employeeForUser(
                    $user->tenant,
                    $user
                );
        } catch (LogicException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 403);
        }

        return response()->json([
            'success' => true,

            'user' =>
                $this->userPayload($user),

            'employee' =>
                $this->employeePayload($employee),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | تسجيل الخروج من الجهاز الحالي
    |--------------------------------------------------------------------------
    */

    public function logout(
        Request $request
    ): JsonResponse {
        $request->user()
            ->currentAccessToken()
            ?->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | تجهيز البيانات
    |--------------------------------------------------------------------------
    */

    private function userPayload(
        User $user
    ): array {
        return [
            'id' =>
                $user->id,

            'name' =>
                $user->name,

            'email' =>
                $user->email,

            'tenant_id' =>
                $user->tenant_id,

            'locale' =>
                $user->locale ?? 'ar',

            'permissions' =>
                $user->getAllPermissions()
                    ->pluck('name')
                    ->values(),
        ];
    }


    private function employeePayload(
        Employee $employee
    ): array {
        $employee->loadMissing([
            'department',
            'jobTitle',
            'workLocation',
        ]);

        return [
            'id' =>
                $employee->id,

            'uuid' =>
                $employee->uuid,

            'employee_number' =>
                $employee->employee_number,

            'full_name' =>
                $this->employeeName($employee),

            'employment_status' =>
                $employee->employment_status,

            'department' =>
                $employee->department
                    ? [
                        'id' =>
                            $employee->department->id,

                        'name' =>
                            $employee->department->name,
                    ]
                    : null,

            'job_title' =>
                $employee->jobTitle
                    ? [
                        'id' =>
                            $employee->jobTitle->id,

                        'name' =>
                            $employee->jobTitle->name,
                    ]
                    : null,

            'work_location' =>
                $employee->workLocation
                    ? [
                        'id' =>
                            $employee->workLocation->id,

                        'name' =>
                            $employee->workLocation->name,
                    ]
                    : null,
        ];
    }


    private function employeeName(
        Employee $employee
    ): string {
        if (filled($employee->full_name)) {
            return trim(
                (string) $employee->full_name
            );
        }

        return trim(
            implode(
                ' ',
                array_filter([
                    $employee->first_name,
                    $employee->father_name,
                    $employee->grandfather_name,
                    $employee->family_name,
                ])
            )
        );
    }


    private function tokenName(
        string $deviceUuid
    ): string {
        return 'mobile-'
            . substr(
                hash('sha256', $deviceUuid),
                0,
                32
            );
    }
}