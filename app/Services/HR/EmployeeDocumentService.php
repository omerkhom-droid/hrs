<?php

namespace App\Services\HR;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

class EmployeeDocumentService
{
    private const DISK = 'local';

    public function create(
        Tenant $tenant,
        User $actor,
        array $data
    ): EmployeeDocument {
        $this->ensureActorBelongsToTenant($actor, $tenant);

        $employee = Employee::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($data['employee_id']);

        /** @var UploadedFile $file */
        $file = $data['file'];
        unset($data['file'], $data['tenant_id']);

        $storedFile = $this->storeFile(
            $tenant,
            $employee,
            $file
        );

        try {
            $document = DB::transaction(function () use (
                $tenant,
                $employee,
                $actor,
                $data,
                $storedFile
            ) {
                $document = new EmployeeDocument([
                    ...$data,
                    ...$storedFile,
                ]);

                $document->tenant_id = $tenant->id;
                $document->employee_id = $employee->id;
                $document->uploaded_by = $actor->id;
                $document->is_verified = false;
                $document->save();

                return $document;
            });
        } catch (Throwable $exception) {
            Storage::disk(self::DISK)->delete(
                $storedFile['file_path']
            );

            throw $exception;
        }

        return $this->loadRelations($document);
    }

    public function update(
        EmployeeDocument $document,
        User $actor,
        array $data
    ): EmployeeDocument {
        $this->ensureDocumentAccess($document, $actor);

        $newStoredFile = null;
        $oldDisk = $document->disk;
        $oldPath = $document->file_path;

        if (($data['file'] ?? null) instanceof UploadedFile) {
            $newStoredFile = $this->storeFile(
                Tenant::query()->findOrFail(
                    $document->tenant_id
                ),
                $document->employee,
                $data['file']
            );
        }

        unset(
            $data['file'],
            $data['tenant_id'],
            $data['employee_id'],
            $data['disk'],
            $data['file_path'],
            $data['uploaded_by'],
            $data['is_verified'],
            $data['verified_at'],
            $data['verified_by']
        );

        try {
            $document = DB::transaction(function () use (
                $document,
                $data,
                $newStoredFile
            ) {
                $document->fill([
                    ...$data,
                    ...($newStoredFile ?? []),
                ]);
                $document->save();

                return $document;
            });
        } catch (Throwable $exception) {
            if ($newStoredFile) {
                Storage::disk(self::DISK)->delete(
                    $newStoredFile['file_path']
                );
            }

            throw $exception;
        }

        if ($newStoredFile && $oldPath) {
            Storage::disk($oldDisk ?: self::DISK)->delete(
                $oldPath
            );
        }

        return $this->loadRelations($document);
    }

    public function verify(
        EmployeeDocument $document,
        User $actor
    ): EmployeeDocument {
        $this->ensureDocumentAccess($document, $actor);

        $document->forceFill([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $actor->id,
        ])->save();

        return $this->loadRelations($document);
    }

    public function unverify(
        EmployeeDocument $document,
        User $actor
    ): EmployeeDocument {
        $this->ensureDocumentAccess($document, $actor);

        $document->forceFill([
            'is_verified' => false,
            'verified_at' => null,
            'verified_by' => null,
        ])->save();

        return $this->loadRelations($document);
    }

    public function archive(
        EmployeeDocument $document,
        User $actor
    ): void {
        $this->ensureDocumentAccess($document, $actor);
        $document->delete();
    }

    public function restore(
        EmployeeDocument $document,
        User $actor
    ): EmployeeDocument {
        $this->ensureDocumentAccess($document, $actor);

        if ($document->trashed()) {
            $document->restore();
        }

        return $this->loadRelations($document);
    }

    private function storeFile(
        Tenant $tenant,
        Employee $employee,
        UploadedFile $file
    ): array {
        $extension = strtolower(
            $file->getClientOriginalExtension()
            ?: (string) $file->guessExtension()
        );

        $fileName = Str::uuid()
            . ($extension ? ".{$extension}" : '');

        $directory = sprintf(
            'tenants/%d/employees/%s/documents',
            $tenant->id,
            $employee->uuid
        );

        $path = $file->storeAs(
            $directory,
            $fileName,
            self::DISK
        );

        if (!$path) {
            throw new LogicException(
                'تعذر حفظ ملف المستند، يرجى المحاولة مرة أخرى.'
            );
        }

        return [
            'disk' => self::DISK,
            'file_path' => $path,
            'original_name' => Str::limit(
                basename($file->getClientOriginalName()),
                255,
                ''
            ),
            'mime_type' => $file->getMimeType(),
            'file_extension' => $extension ?: null,
            'file_size' => (int) $file->getSize(),
        ];
    }

    private function loadRelations(
        EmployeeDocument $document
    ): EmployeeDocument {
        return $document->load([
            'employee:id,tenant_id,employee_number,department_id,first_name,father_name,grandfather_name,family_name',
            'employee.department:id,tenant_id,name',
            'uploadedBy:id,tenant_id,name',
            'verifiedBy:id,tenant_id,name',
        ]);
    }

    private function ensureActorBelongsToTenant(
        User $actor,
        Tenant $tenant
    ): void {
        if ((int) $actor->tenant_id !== (int) $tenant->id) {
            throw new LogicException(
                'لا يمكن إدارة مستندات شركة أخرى.'
            );
        }
    }

    private function ensureDocumentAccess(
        EmployeeDocument $document,
        User $actor
    ): void {
        if ((int) $document->tenant_id !== (int) $actor->tenant_id) {
            throw new LogicException(
                'لا يمكن الوصول إلى مستند تابع لشركة أخرى.'
            );
        }
    }
}
