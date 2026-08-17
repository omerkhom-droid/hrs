<?php

namespace App\Services\HR;

use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Throwable;

class EmployeeService
{
    private const PHOTO_DISK = 'public';

    public function create(
        Tenant $tenant,
        array $data
    ): Employee {
        $photo = $this->extractPhoto($data);
        $photoPath = null;

        unset(
            $data['tenant_id'],
            $data['uuid'],
            $data['remove_photo']
        );

        try {
            $employee = DB::transaction(function () use (
                $tenant,
                $data,
                $photo,
                &$photoPath
            ) {
                $employee = new Employee($data);
                $employee->tenant_id = $tenant->id;
                $employee->save();

                if ($photo) {
                    $photoPath = $this->storePhoto(
                        $employee,
                        $photo
                    );

                    $employee->forceFill([
                        'photo_path' => $photoPath,
                    ])->save();
                }

                $this->disableUserWhenAccessIsBlocked(
                    $employee
                );

                return $employee;
            });
        } catch (Throwable $exception) {
            $this->deletePhoto($photoPath);

            throw $exception;
        }

        return $this->loadEmployeeRelations(
            $employee
        );
    }

    public function update(
        Employee $employee,
        array $data
    ): Employee {
        $this->ensureEmployeeBelongsToCurrentTenant(
            $employee
        );

        $photo = $this->extractPhoto($data);
        $removePhoto = (bool) ($data['remove_photo'] ?? false);
        $oldPhotoPath = $employee->photo_path;
        $newPhotoPath = null;

        unset(
            $data['tenant_id'],
            $data['uuid'],
            $data['remove_photo']
        );

        try {
            DB::transaction(function () use (
                $employee,
                $data,
                $photo,
                $removePhoto,
                $oldPhotoPath,
                &$newPhotoPath
            ) {
                $employee->fill($data);

                if ($photo) {
                    $newPhotoPath = $this->storePhoto(
                        $employee,
                        $photo
                    );

                    $employee->photo_path = $newPhotoPath;
                } elseif ($removePhoto) {
                    $employee->photo_path = null;
                }

                $employee->save();

                $this->disableUserWhenAccessIsBlocked(
                    $employee
                );

                if (
                    $oldPhotoPath &&
                    ($newPhotoPath || $removePhoto)
                ) {
                    DB::afterCommit(function () use ($oldPhotoPath) {
                        $this->deletePhoto($oldPhotoPath);
                    });
                }
            });
        } catch (Throwable $exception) {
            $this->deletePhoto($newPhotoPath);

            throw $exception;
        }

        return $this->loadEmployeeRelations(
            $employee
        );
    }

    public function archive(
        Employee $employee
    ): void {
        $this->ensureEmployeeBelongsToCurrentTenant(
            $employee
        );

        $hasActiveSubordinates = $employee
            ->subordinates()
            ->whereNotIn('employment_status', [
                'terminated',
            ])
            ->exists();

        if ($hasActiveSubordinates) {
            throw new LogicException(
                'لا يمكن أرشفة المدير قبل نقل الموظفين التابعين له إلى مدير آخر.'
            );
        }

        DB::transaction(function () use ($employee) {
            if ($employee->user) {
                $employee->user->forceFill([
                    'is_active' => false,
                ])->save();
            }

            $employee->delete();
        });
    }

    public function restore(
        Employee $employee
    ): Employee {
        $this->ensureEmployeeBelongsToCurrentTenant(
            $employee
        );

        if ($employee->trashed()) {
            $employee->restore();
        }

        /*
         * لا يتم تفعيل حساب الدخول تلقائيًا لأسباب أمنية.
         * يعاد تفعيله من شاشة المستخدمين بعد مراجعة الصلاحيات.
         */
        return $this->loadEmployeeRelations(
            $employee
        );
    }

    private function extractPhoto(
        array &$data
    ): ?UploadedFile {
        $photo = $data['photo'] ?? null;

        unset($data['photo']);

        return $photo instanceof UploadedFile
            ? $photo
            : null;
    }

    private function storePhoto(
        Employee $employee,
        UploadedFile $photo
    ): string {
        return $photo->store(
            sprintf(
                'tenants/%d/employees/%s',
                $employee->tenant_id,
                $employee->uuid
            ),
            self::PHOTO_DISK
        );
    }

    private function deletePhoto(
        ?string $path
    ): void {
        if (!$path) {
            return;
        }

        Storage::disk(
            self::PHOTO_DISK
        )->delete($path);
    }

    private function disableUserWhenAccessIsBlocked(
        Employee $employee
    ): void {
        if (
            !$employee->user_id ||
            !in_array(
                $employee->employment_status,
                ['suspended', 'terminated'],
                true
            )
        ) {
            return;
        }

        $employee->user()
            ->update([
                'is_active' => false,
            ]);
    }

    private function ensureEmployeeBelongsToCurrentTenant(
        Employee $employee
    ): void {
        $tenantId = auth()->user()?->tenant_id;

        if (
            $tenantId &&
            (int) $employee->tenant_id !== (int) $tenantId
        ) {
            throw new LogicException(
                'لا يمكن إدارة موظف تابع لشركة أخرى.'
            );
        }
    }

    private function loadEmployeeRelations(
        Employee $employee
    ): Employee {
        return $employee->refresh()->load([
            'user:id,tenant_id,name,email,is_active',
            'branch:id,tenant_id,code,name',
            'department:id,tenant_id,branch_id,code,name',
            'jobTitle:id,tenant_id,department_id,code,name',
            'workLocation:id,tenant_id,branch_id,code,name',
            'manager:id,tenant_id,employee_number,first_name,father_name,family_name',
        ]);
    }
}