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

class SelfServiceEmployeeLoanController extends Controller
{
    public function __construct(
        private readonly EmployeeLoanService $loanService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | صفحة سلف الموظف
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $this->authorizeSelfService($request);
        $this->currentEmployee($request);

        return view(
            'tenant.payroll.employee-loans.self-service'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | قائمة سلف الموظف
    |--------------------------------------------------------------------------
    */

    public function data(Request $request): JsonResponse
    {
        $this->authorizeSelfService($request);

        $validated = $request->validate([
            'status' => [
                'nullable',
                'in:draft,submitted,approved,rejected,active,completed,cancelled',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'between:10,50',
            ],
        ]);

        $employee = $this->currentEmployee($request);

        $loans = EmployeeLoan::query()
            ->where(
                'tenant_id',
                $request->user()->tenant_id
            )
            ->where(
                'employee_id',
                $employee->id
            )
            ->withCount('installments')
            ->status($validated['status'] ?? null)
            ->latest('id')
            ->paginate(
                $validated['per_page'] ?? 10
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
        $this->authorizeSelfService($request);

        $employee = $this->currentEmployee($request);

        return response()->json([
            'success' => true,

            'data' => [
                'employee' => [
                    'id' => $employee->id,

                    'employee_number' =>
                        $employee->employee_number,

                    'name' =>
                        $this->employeeName($employee),
                ],

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
        $this->authorizeSelfService($request);

        $tenant = $this->currentTenant($request);
        $employee = $this->currentEmployee($request);

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
            'message' => 'تم حفظ طلب السلفة كمسودة.',
            'data' => $this->loanPayload($loan),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | عرض الطلب
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        EmployeeLoan $employeeLoan
    ): JsonResponse {
        $this->authorizeSelfService($request);

        $loan = $this->resolveEmployeeLoan(
            $request,
            $employeeLoan
        );

        $loan->load([
            'employee',
            'installments',
            'approver:id,name',
            'rejecter:id,name',
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
        $this->authorizeSelfService($request);

        $loan = $this->resolveEmployeeLoan(
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
            'message' => 'تم تحديث مسودة السلفة.',
            'data' => $this->loanPayload($loan),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | إرسال الطلب للاعتماد
    |--------------------------------------------------------------------------
    */

    public function submit(
        Request $request,
        EmployeeLoan $employeeLoan
    ): JsonResponse {
        $this->authorizeSelfService($request);

        $loan = $this->resolveEmployeeLoan(
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
            'message' => 'تم إرسال طلب السلفة للاعتماد.',
            'data' => $this->loanPayload($loan),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | إلغاء الطلب
    |--------------------------------------------------------------------------
    */

    public function cancel(
        CancelEmployeeLoanRequest $request,
        EmployeeLoan $employeeLoan
    ): JsonResponse {
        $this->authorizeSelfService($request);

        $loan = $this->resolveEmployeeLoan(
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
            'message' => 'تم إلغاء طلب السلفة.',
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
        $this->authorizeSelfService($request);

        $loan = $this->resolveEmployeeLoan(
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
            'message' => 'تم حذف مسودة السلفة.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | تجهيز بيانات السلفة
    |--------------------------------------------------------------------------
    */

    private function loanPayload(
        EmployeeLoan $loan,
        bool $withInstallments = false
    ): array {
        $payload = [
            'id' => $loan->id,
            'uuid' => $loan->uuid,

            'request_number' =>
                $loan->request_number,

            'loan_type' =>
                $loan->loan_type,

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

            'status_label' =>
                $loan->status_label,

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
                        'id' =>
                            $installment->id,

                        'uuid' =>
                            $installment->uuid,

                        'installment_number' =>
                            $installment
                                ->installment_number,

                        'due_date' =>
                            $installment
                                ->due_date
                                ?->toDateString(),

                        'amount' =>
                            (float) $installment
                                ->amount,

                        'paid_amount' =>
                            (float) $installment
                                ->paid_amount,

                        'remaining_amount' =>
                            (float) $installment
                                ->remaining_amount,

                        'status' =>
                            $installment->status,

                        'status_label' =>
                            $installment
                                ->status_label,

                        'deducted_at' =>
                            $installment
                                ->deducted_at
                                ?->toDateString(),
                    ])
                    ->values();
        }

        return $payload;
    }

    /*
    |--------------------------------------------------------------------------
    | التحقق من ملكية الطلب
    |--------------------------------------------------------------------------
    */

    private function resolveEmployeeLoan(
        Request $request,
        EmployeeLoan $employeeLoan
    ): EmployeeLoan {
        $employee = $this->currentEmployee($request);

        abort_unless(
            (int) $employeeLoan->tenant_id ===
                (int) $request->user()->tenant_id &&
            (int) $employeeLoan->employee_id ===
                (int) $employee->id,
            404
        );

        return $employeeLoan;
    }

    private function currentEmployee(
        Request $request
    ): Employee {
        return Employee::withoutGlobalScopes()
            ->where(
                'tenant_id',
                $request->user()->tenant_id
            )
            ->where(
                'user_id',
                $request->user()->id
            )
            ->whereNull('deleted_at')
            ->firstOrFail();
    }

    private function currentTenant(
        Request $request
    ): Tenant {
        return Tenant::query()->findOrFail(
            $request->user()->tenant_id
        );
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

    private function authorizeSelfService(
        Request $request
    ): void {
        abort_unless(
            $request->user()?->can(
                'self_service.loans'
            ),
            403,
            'لا تملك صلاحية طلب السلف.'
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