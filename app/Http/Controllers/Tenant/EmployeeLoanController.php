<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CancelEmployeeLoanRequest;
use App\Http\Requests\Tenant\StoreEmployeeLoanRequest;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\Tenant;
use App\Services\HR\EmployeeLoanService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeLoanController extends Controller
{
    public function __construct(
        private readonly EmployeeLoanService $loanService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | صفحة إدارة السلف
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $this->authorizeManagement($request);

        return view('tenant.payroll.employee-loans.index');
    }

    /*
    |--------------------------------------------------------------------------
    | بيانات الجدول
    |--------------------------------------------------------------------------
    */

    public function data(Request $request): JsonResponse
    {
        $this->authorizeView($request);

        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
            'status' => [
                'nullable',
                'in:draft,submitted,approved,rejected,active,completed,cancelled',
            ],
            'employee_id' => [
                'nullable',
                'integer',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'between:10,100',
            ],
        ]);

        $tenant = $this->currentTenant($request);

        $loans = EmployeeLoan::query()
            ->where('tenant_id', $tenant->id)
            ->with([
                'employee',
                'approver:id,name',
                'creator:id,name',
            ])
            ->withCount('installments')
            ->search($validated['search'] ?? null)
            ->status($validated['status'] ?? null)
            ->when(
                !empty($validated['employee_id']),
                fn ($query) =>
                    $query->where(
                        'employee_id',
                        $validated['employee_id']
                    )
            )
            ->latest('id')
            ->paginate(
                $validated['per_page'] ?? 20
            );

        return response()->json([
            'success' => true,

            'data' => $loans->getCollection()
                ->map(
                    fn (EmployeeLoan $loan) =>
                        $this->loanPayload($loan)
                )
                ->values(),

            'meta' => [
                'current_page' =>
                    $loans->currentPage(),

                'last_page' =>
                    $loans->lastPage(),

                'per_page' =>
                    $loans->perPage(),

                'total' =>
                    $loans->total(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | خيارات النموذج
    |--------------------------------------------------------------------------
    */

    public function options(Request $request): JsonResponse
    {
        $this->authorizeManagement($request);

        $tenant = $this->currentTenant($request);

        $employees = Employee::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('deleted_at')
            ->orderBy('first_name')
            ->orderBy('family_name')
            ->get()
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,

                'employee_number' =>
                    $employee->employee_number,

                'name' =>
                    $this->employeeName($employee),
            ])
            ->values();

        return response()->json([
            'success' => true,

            'data' => [
                'employees' => $employees,

                'loan_types' => [
                    [
                        'value' => 'salary_advance',
                        'label' => 'سلفة راتب',
                    ],
                    [
                        'value' => 'personal_loan',
                        'label' => 'سلفة شخصية',
                    ],
                    [
                        'value' => 'emergency_loan',
                        'label' => 'سلفة طارئة',
                    ],
                ],

                'maximum_installments' => 60,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | إنشاء مسودة
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreEmployeeLoanRequest $request
    ): JsonResponse {
        $this->authorizeManagement($request);

        $tenant = $this->currentTenant($request);

        $employee = Employee::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail(
                $request->integer('employee_id')
            );

        try {
            $loan = $this->loanService->createDraft(
                $tenant,
                $employee,
                $request->user(),
                $request->validated()
            );
        } catch (DomainException $exception) {
            return $this->domainError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء مسودة السلفة بنجاح.',
            'data' => $this->loanPayload($loan),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | عرض سلفة
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        EmployeeLoan $employeeLoan
    ): JsonResponse {
        $this->authorizeView($request);

        $loan = $this->resolveLoan(
            $request,
            $employeeLoan
        );

        $loan->load([
            'employee',
            'installments',
            'approver:id,name',
            'rejecter:id,name',
            'canceller:id,name',
            'creator:id,name',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->loanPayload(
                $loan,
                true
            ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | تعديل المسودة
    |--------------------------------------------------------------------------
    */

    public function update(
        StoreEmployeeLoanRequest $request,
        EmployeeLoan $employeeLoan
    ): JsonResponse {
        $this->authorizeManagement($request);

        $loan = $this->resolveLoan(
            $request,
            $employeeLoan
        );

        try {
            $loan = $this->loanService->updateDraft(
                $loan,
                $request->validated()
            );
        } catch (DomainException $exception) {
            return $this->domainError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث مسودة السلفة بنجاح.',
            'data' => $this->loanPayload($loan),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | إرسال السلفة للاعتماد
    |--------------------------------------------------------------------------
    */

    public function submit(
        Request $request,
        EmployeeLoan $employeeLoan
    ): JsonResponse {
        $this->authorizeManagement($request);

        $loan = $this->resolveLoan(
            $request,
            $employeeLoan
        );

        try {
            $loan = $this->loanService->submit($loan);
        } catch (DomainException $exception) {
            return $this->domainError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال السلفة للاعتماد.',
            'data' => $this->loanPayload($loan),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | إلغاء السلفة
    |--------------------------------------------------------------------------
    */

    public function cancel(
        CancelEmployeeLoanRequest $request,
        EmployeeLoan $employeeLoan
    ): JsonResponse {
        $this->authorizeManagement($request);

        $loan = $this->resolveLoan(
            $request,
            $employeeLoan
        );

        try {
            $loan = $this->loanService->cancel(
                $loan,
                $request->user(),
                $request->input(
                    'cancellation_reason'
                )
            );
        } catch (DomainException $exception) {
            return $this->domainError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء السلفة بنجاح.',
            'data' => $this->loanPayload($loan),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | حذف المسودة
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        EmployeeLoan $employeeLoan
    ): JsonResponse {
        $this->authorizeManagement($request);

        $loan = $this->resolveLoan(
            $request,
            $employeeLoan
        );

        try {
            $this->loanService->deleteDraft($loan);
        } catch (DomainException $exception) {
            return $this->domainError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حذف مسودة السلفة بنجاح.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | تجهيز البيانات
    |--------------------------------------------------------------------------
    */

    private function loanPayload(
        EmployeeLoan $loan,
        bool $withInstallments = false
    ): array {
        $loan->loadMissing('employee');

        $payload = [
            'id' => $loan->id,
            'uuid' => $loan->uuid,

            'request_number' =>
                $loan->request_number,

            'loan_type' => $loan->loan_type,

            'loan_type_label' =>
                $loan->loan_type_label,

            'requested_amount' =>
                (float) $loan->requested_amount,

            'approved_amount' =>
                $loan->approved_amount !== null
                    ? (float) $loan->approved_amount
                    : null,

            'installments_count' =>
                $loan->installments_count,

            'installment_amount' =>
                $loan->installment_amount !== null
                    ? (float) $loan->installment_amount
                    : null,

            'paid_amount' =>
                (float) $loan->paid_amount,

            'remaining_amount' =>
                (float) $loan->remaining_amount,

            'first_installment_date' =>
                $loan->first_installment_date
                    ?->toDateString(),

            'status' => $loan->status,
            'status_label' => $loan->status_label,

            'reason' => $loan->reason,

            'employee_notes' =>
                $loan->employee_notes,

            'approval_notes' =>
                $loan->approval_notes,

            'rejection_reason' =>
                $loan->rejection_reason,

            'cancellation_reason' =>
                $loan->cancellation_reason,

            'submitted_at' =>
                $loan->submitted_at
                    ?->toIso8601String(),

            'approved_at' =>
                $loan->approved_at
                    ?->toIso8601String(),

            'rejected_at' =>
                $loan->rejected_at
                    ?->toIso8601String(),

            'cancelled_at' =>
                $loan->cancelled_at
                    ?->toIso8601String(),

            'completed_at' =>
                $loan->completed_at
                    ?->toIso8601String(),

            'employee' => [
                'id' => $loan->employee->id,

                'employee_number' =>
                    $loan->employee->employee_number,

                'name' =>
                    $this->employeeName(
                        $loan->employee
                    ),
            ],

            'can_edit' => $loan->canBeEdited(),

            'can_submit' =>
                $loan->canBeSubmitted(),

            'can_cancel' =>
                $loan->canBeCancelled(),

            'created_at' =>
                $loan->created_at
                    ?->toIso8601String(),
        ];

        if ($withInstallments) {
            $loan->loadMissing('installments');

            $payload['installments'] =
                $loan->installments
                    ->map(fn ($installment) => [
                        'id' => $installment->id,
                        'uuid' => $installment->uuid,

                        'installment_number' =>
                            $installment
                                ->installment_number,

                        'due_date' =>
                            $installment
                                ->due_date
                                ?->toDateString(),

                        'amount' =>
                            (float) $installment->amount,

                        'paid_amount' =>
                            (float) $installment
                                ->paid_amount,

                        'remaining_amount' =>
                            (float) $installment
                                ->remaining_amount,

                        'status' =>
                            $installment->status,

                        'status_label' =>
                            $installment->status_label,

                        'deducted_at' =>
                            $installment
                                ->deducted_at
                                ?->toDateString(),

                        'paid_at' =>
                            $installment
                                ->paid_at
                                ?->toIso8601String(),
                    ])
                    ->values();
        }

        return $payload;
    }

    private function resolveLoan(
        Request $request,
        EmployeeLoan $employeeLoan
    ): EmployeeLoan {
        abort_unless(
            (int) $employeeLoan->tenant_id ===
            (int) $request->user()->tenant_id,
            404
        );

        return $employeeLoan;
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

    private function authorizeView(
        Request $request
    ): void {
        abort_unless(
            $request->user()?->can('loans.view') ||
            $request->user()?->can('loans.manage') ||
            $request->user()?->can('loans.approve'),
            403,
            'لا تملك صلاحية عرض السلف.'
        );
    }

    private function authorizeManagement(
        Request $request
    ): void {
        abort_unless(
            $request->user()?->can('loans.manage'),
            403,
            'لا تملك صلاحية إدارة السلف.'
        );
    }

    private function currentTenant(
        Request $request
    ): Tenant {
        return Tenant::query()->findOrFail(
            $request->user()->tenant_id
        );
    }

    private function domainError(
        DomainException $exception
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
        ], 422);
    }
}