<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreEmployeeDocumentRequest;
use App\Http\Requests\Tenant\UpdateEmployeeDocumentRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Tenant;
use App\Services\HR\EmployeeDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use LogicException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    public function __construct(
        private readonly EmployeeDocumentService $documentService
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorizePermission($request, 'documents.view');

        return view('tenant.documents.index');
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'documents.view');

        $perPage = min(
            max($request->integer('per_page', 15), 10),
            100
        );

        $allowedTypes = array_keys($this->documentTypes());
        $allowedSorts = [
            'title',
            'document_type',
            'document_number',
            'issue_date',
            'expiry_date',
            'is_verified',
            'created_at',
        ];

        $sortBy = (string) $request->input(
            'sort_by',
            'created_at'
        );
        $sortDirection = strtolower(
            (string) $request->input('sort_direction', 'desc')
        );

        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        if (!in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        $query = EmployeeDocument::query()
            ->with([
                'employee:id,tenant_id,employee_number,department_id,first_name,father_name,grandfather_name,family_name',
                'employee.department:id,tenant_id,name',
                'uploadedBy:id,tenant_id,name',
                'verifiedBy:id,tenant_id,name',
            ])
            ->search($request->input('search'));

        $archiveStatus = (string) $request->input(
            'archive_status',
            'active'
        );

        if ($archiveStatus === 'only') {
            $query->onlyTrashed();
        } elseif ($archiveStatus === 'with') {
            $query->withTrashed();
        }

        $type = (string) $request->input('document_type', '');

        if (in_array($type, $allowedTypes, true)) {
            $query->where('document_type', $type);
        }

        if ($request->filled('employee_id')) {
            $query->where(
                'employee_id',
                $request->integer('employee_id')
            );
        }

        $verification = (string) $request->input(
            'verification',
            ''
        );

        if ($verification === 'verified') {
            $query->where('is_verified', true);
        } elseif ($verification === 'unverified') {
            $query->where('is_verified', false);
        }

        $expiryStatus = (string) $request->input(
            'expiry_status',
            ''
        );

        match ($expiryStatus) {
            'expired' => $query
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<', today()),
            'expiring' => $query->expiringWithin(30),
            'valid' => $query
                ->whereDate('expiry_date', '>', today()->addDays(30)),
            'no_expiry' => $query->whereNull('expiry_date'),
            default => null,
        };

        $documents = $query
            ->orderBy($sortBy, $sortDirection)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $documents->through(
            fn (EmployeeDocument $document) =>
                $this->listPayload($document)
        );

        return response()->json([
            ...$documents->toArray(),
            'summary' => $this->summaryPayload(),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorizeAnyPermission($request, [
            'documents.view',
            'documents.manage',
        ]);

        $employees = Employee::query()
            ->whereNotIn('employment_status', ['terminated'])
            ->orderBy('first_name')
            ->orderBy('family_name')
            ->get([
                'id',
                'employee_number',
                'first_name',
                'father_name',
                'grandfather_name',
                'family_name',
            ])
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'name' => $employee->full_name,
            ]);

        $types = collect($this->documentTypes())
            ->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $label,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'options' => [
                'employees' => $employees,
                'document_types' => $types,
                'max_file_size_mb' => 10,
                'allowed_extensions' => [
                    'pdf',
                    'jpg',
                    'jpeg',
                    'png',
                    'webp',
                    'doc',
                    'docx',
                    'xls',
                    'xlsx',
                ],
            ],
        ]);
    }

    public function store(
        StoreEmployeeDocumentRequest $request
    ): JsonResponse {
        try {
            $document = $this->documentService->create(
                $this->currentTenant($request),
                $request->user(),
                $request->validated()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم رفع مستند الموظف بنجاح.',
            'document' => $this->detailsPayload($document),
        ], 201);
    }

    public function show(
        Request $request,
        EmployeeDocument $document
    ): JsonResponse {
        $this->authorizePermission($request, 'documents.view');
        $this->ensureSameTenant($request, $document);

        return response()->json([
            'success' => true,
            'document' => $this->detailsPayload($document),
        ]);
    }

    public function update(
        UpdateEmployeeDocumentRequest $request,
        EmployeeDocument $document
    ): JsonResponse {
        try {
            $document = $this->documentService->update(
                $document,
                $request->user(),
                $request->validated()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المستند بنجاح.',
            'document' => $this->detailsPayload($document),
        ]);
    }

    public function verify(
        Request $request,
        EmployeeDocument $document
    ): JsonResponse {
        $this->authorizePermission($request, 'documents.manage');

        try {
            $document = $this->documentService->verify(
                $document,
                $request->user()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم اعتماد المستند بنجاح.',
            'document' => $this->detailsPayload($document),
        ]);
    }

    public function unverify(
        Request $request,
        EmployeeDocument $document
    ): JsonResponse {
        $this->authorizePermission($request, 'documents.manage');

        try {
            $document = $this->documentService->unverify(
                $document,
                $request->user()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء اعتماد المستند.',
            'document' => $this->detailsPayload($document),
        ]);
    }

    public function destroy(
        Request $request,
        EmployeeDocument $document
    ): JsonResponse {
        $this->authorizePermission($request, 'documents.manage');

        try {
            $this->documentService->archive(
                $document,
                $request->user()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تمت أرشفة المستند بنجاح.',
        ]);
    }

    public function restore(
        Request $request,
        EmployeeDocument $document
    ): JsonResponse {
        $this->authorizePermission($request, 'documents.manage');

        try {
            $document = $this->documentService->restore(
                $document,
                $request->user()
            );
        } catch (LogicException $exception) {
            return $this->logicError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تمت استعادة المستند بنجاح.',
            'document' => $this->detailsPayload($document),
        ]);
    }

    public function preview(
        Request $request,
        EmployeeDocument $document
    ): StreamedResponse {
        $this->authorizePermission($request, 'documents.view');
        $this->ensureSameTenant($request, $document);
        $this->ensureFileExists($document);

        abort_unless(
            $document->is_previewable,
            415,
            'هذا النوع من الملفات لا يدعم المعاينة داخل المتصفح.'
        );

        return Storage::disk($document->disk)->response(
            $document->file_path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type
                    ?: 'application/octet-stream',
                'Content-Disposition' => 'inline',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    public function download(
        Request $request,
        EmployeeDocument $document
    ): StreamedResponse {
        $this->authorizePermission($request, 'documents.view');
        $this->ensureSameTenant($request, $document);
        $this->ensureFileExists($document);

        return Storage::disk($document->disk)->download(
            $document->file_path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type
                    ?: 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    private function currentTenant(Request $request): Tenant
    {
        return Tenant::query()->findOrFail(
            $request->user()->tenant_id
        );
    }

    private function documentTypes(): array
    {
        return [
            'identity' => 'هوية وطنية',
            'passport' => 'جواز سفر',
            'residency' => 'إقامة',
            'contract' => 'عقد',
            'qualification' => 'مؤهل علمي',
            'certificate' => 'شهادة',
            'medical' => 'مستند طبي',
            'insurance' => 'تأمين',
            'bank' => 'مستند بنكي',
            'license' => 'رخصة',
            'other' => 'أخرى',
        ];
    }

    private function summaryPayload(): array
    {
        $query = EmployeeDocument::query();

        return [
            'total' => (clone $query)->count(),
            'verified' => (clone $query)
                ->where('is_verified', true)
                ->count(),
            'expiring' => (clone $query)
                ->expiringWithin(30)
                ->count(),
            'expired' => (clone $query)
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<', today())
                ->count(),
        ];
    }

    private function loadDocument(
        EmployeeDocument $document
    ): EmployeeDocument {
        return $document->load([
            'employee:id,tenant_id,employee_number,department_id,first_name,father_name,grandfather_name,family_name',
            'employee.department:id,tenant_id,name',
            'uploadedBy:id,tenant_id,name',
            'verifiedBy:id,tenant_id,name',
        ]);
    }

    private function listPayload(
        EmployeeDocument $document
    ): array {
        return [
            'id' => $document->id,
            'uuid' => $document->uuid,
            'employee' => $document->employee
                ? [
                    'id' => $document->employee->id,
                    'employee_number' =>
                        $document->employee->employee_number,
                    'name' => $document->employee->full_name,
                    'department' =>
                        $document->employee->department?->name,
                ]
                : null,
            'document_type' => $document->document_type,
            'document_type_label' =>
                $document->document_type_label,
            'document_number' => $document->document_number,
            'title' => $document->title,
            'issue_date' => $document->issue_date?->toDateString(),
            'expiry_date' => $document->expiry_date?->toDateString(),
            'expiry_status' => $document->expiry_status,
            'expiry_status_label' =>
                $document->expiry_status_label,
            'original_name' => $document->original_name,
            'formatted_file_size' =>
                $document->formatted_file_size,
            'is_previewable' => $document->is_previewable,
            'is_verified' => $document->is_verified,
            'verified_at' => $document->verified_at?->toISOString(),
            'is_archived' => $document->trashed(),
            'created_at' => $document->created_at?->toISOString(),
            'preview_url' => $document->is_previewable
                ? route('app.documents.preview', $document)
                : null,
            'download_url' => route(
                'app.documents.download',
                $document
            ),
        ];
    }

    private function detailsPayload(
        EmployeeDocument $document
    ): array {
        $document = $this->loadDocument($document);

        return [
            ...$document->toArray(),
            'preview_url' => $document->is_previewable
                ? route('app.documents.preview', $document)
                : null,
            'download_url' => route(
                'app.documents.download',
                $document
            ),
        ];
    }

    private function ensureSameTenant(
        Request $request,
        EmployeeDocument $document
    ): void {
        abort_unless(
            (int) $request->user()->tenant_id
                === (int) $document->tenant_id,
            404
        );
    }

    private function ensureFileExists(
        EmployeeDocument $document
    ): void {
        abort_unless(
            Storage::disk($document->disk)->exists(
                $document->file_path
            ),
            404,
            'ملف المستند غير موجود في التخزين.'
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

    private function authorizePermission(
        Request $request,
        string $permission
    ): void {
        abort_unless(
            $request->user()?->can($permission),
            403,
            'ليس لديك صلاحية لتنفيذ هذا الإجراء.'
        );
    }

    private function authorizeAnyPermission(
        Request $request,
        array $permissions
    ): void {
        $allowed = collect($permissions)->contains(
            fn (string $permission) =>
                $request->user()?->can($permission)
        );

        abort_unless(
            $allowed,
            403,
            'ليس لديك صلاحية لتنفيذ هذا الإجراء.'
        );
    }
}
