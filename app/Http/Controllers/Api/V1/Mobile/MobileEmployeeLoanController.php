<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MobileStoreEmployeeLoanRequest;
use App\Http\Requests\Tenant\CancelEmployeeLoanRequest;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\Tenant;
use App\Services\HR\EmployeeLoanService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileEmployeeLoanController extends Controller
{
    public function __construct(
        private readonly EmployeeLoanService $loanService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeSelfService($request);
        $employee = $this->currentEmployee($request);

        $validated = $request->validate([
            'status' => [
                'nullable',
                'in:draft,submitted,approved,rejected,active,completed,cancelled',
            ],
            'per_page' => ['nullable', 'integer', 'between:10,50'],
        ]);

        $loans = EmployeeLoan::withoutGlobalScopes()
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('employee_id', $employee->id)
            ->whereNull('deleted_at')
            ->status($validated['status'] ?? null)
            ->latest('id')
            ->paginate($validated['per_page'] ?? 10);

        return response()->json([
            'success' => true,
            'data' => $loans->getCollection()
                ->map(fn (EmployeeLoan $loan) => $this->loanPayload($loan))
                ->values(),
            'meta' => [
                'current_page' => $loans->currentPage(),
                'last_page' => $loans->lastPage(),
                'per_page' => $loans->perPage(),
                'total' => $loans->total(),
            ],
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorizeSelfService($request);
        $employee = $this->currentEmployee($request);

        return response()->json([
            'success' => true,
            'data' => [
                'employee' => [
                    'id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'name' => $this->employeeName($employee),
                ],
                'loan_types' => [
                    ['value' => 'salary_advance', 'label' => 'سلفة راتب'],
                    ['value' => 'personal_loan', 'label' => 'سلفة شخصية'],
                    ['value' => 'emergency_loan', 'label' => 'سلفة طارئة'],
                ],
                'maximum_installments' => 60,
            ],
        ]);
    }

    public function store(
        MobileStoreEmployeeLoanRequest $request
    ): JsonResponse {
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

    public function show(
        Request $request,
        EmployeeLoan $employeeLoan
    ): JsonResponse {
        $loan = $this->resolveLoan($request, $employeeLoan);
        $loan->load('installments');

        return response()->json([
            'success' => true,
            'data' => $this->loanPayload($loan, true),
        ]);
    }

    public function update(
        MobileStoreEmployeeLoanRequest $request,
        EmployeeLoan $employeeLoan
    ): JsonResponse {
        $loan = $this->resolveLoan($request, $employeeLoan);

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

    public function submit(
        Request $request,
        EmployeeLoan $employeeLoan
    ): JsonResponse {
        $loan = $this->resolveLoan($request, $employeeLoan);

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

    public function cancel(
        CancelEmployeeLoanRequest $request,
        EmployeeLoan $employeeLoan
    ): JsonResponse {
        $loan = $this->resolveLoan($request, $employeeLoan);

        try {
            $loan = $this->loanService->cancel(
                $loan,
                $request->user(),
                $request->input('cancellation_reason')
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

    public function destroy(
        Request $request,
        EmployeeLoan $employeeLoan
    ): JsonResponse {
        $loan = $this->resolveLoan($request, $employeeLoan);

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

    private function loanPayload(
        EmployeeLoan $loan,
        bool $withInstallments = false
    ): array {
        $payload = [
            'uuid' => $loan->uuid,
            'request_number' => $loan->request_number,
            'loan_type' => $loan->loan_type,
            'loan_type_label' => $loan->loan_type_label,
            'requested_amount' => (float) $loan->requested_amount,
            'approved_amount' => $loan->approved_amount !== null
                ? (float) $loan->approved_amount
                : null,
            'installments_count' => (int) $loan->installments_count,
            'installment_amount' => $loan->installment_amount !== null
                ? (float) $loan->installment_amount
                : null,
            'paid_amount' => (float) $loan->paid_amount,
            'remaining_amount' => (float) $loan->remaining_amount,
            'first_installment_date' => $loan->first_installment_date?->toDateString(),
            'status' => $loan->status,
            'status_label' => $loan->status_label,
            'reason' => $loan->reason,
            'employee_notes' => $loan->employee_notes,
            'approval_notes' => $loan->approval_notes,
            'rejection_reason' => $loan->rejection_reason,
            'cancellation_reason' => $loan->cancellation_reason,
            'submitted_at' => $loan->submitted_at?->toIso8601String(),
            'approved_at' => $loan->approved_at?->toIso8601String(),
            'rejected_at' => $loan->rejected_at?->toIso8601String(),
            'cancelled_at' => $loan->cancelled_at?->toIso8601String(),
            'completed_at' => $loan->completed_at?->toIso8601String(),
            'can_edit' => $loan->canBeEdited(),
            'can_submit' => $loan->canBeSubmitted(),
            'can_cancel' => $loan->canBeCancelled(),
            'created_at' => $loan->created_at?->toIso8601String(),
        ];

        if ($withInstallments) {
            $loan->loadMissing('installments');
            $payload['installments'] = $loan->installments
                ->map(fn ($installment) => [
                    'uuid' => $installment->uuid,
                    'installment_number' => (int) $installment->installment_number,
                    'due_date' => $installment->due_date?->toDateString(),
                    'amount' => (float) $installment->amount,
                    'paid_amount' => (float) $installment->paid_amount,
                    'remaining_amount' => (float) $installment->remaining_amount,
                    'status' => $installment->status,
                    'status_label' => $installment->status_label,
                    'deducted_at' => $installment->deducted_at?->toDateString(),
                ])
                ->values();
        }

        return $payload;
    }

    private function resolveLoan(
        Request $request,
        EmployeeLoan $employeeLoan
    ): EmployeeLoan {
        $this->authorizeSelfService($request);
        $employee = $this->currentEmployee($request);

        abort_unless(
            (int) $employeeLoan->tenant_id === (int) $request->user()->tenant_id &&
            (int) $employeeLoan->employee_id === (int) $employee->id,
            404
        );

        return $employeeLoan;
    }

    private function currentEmployee(Request $request): Employee
    {
        return Employee::withoutGlobalScopes()
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('user_id', $request->user()->id)
            ->whereNull('deleted_at')
            ->firstOrFail();
    }

    private function currentTenant(Request $request): Tenant
    {
        return Tenant::query()->findOrFail(
            $request->user()->tenant_id
        );
    }

    private function employeeName(Employee $employee): string
    {
        if (filled($employee->full_name)) {
            return trim((string) $employee->full_name);
        }

        return trim(implode(' ', array_filter([
            $employee->first_name,
            $employee->father_name,
            $employee->grandfather_name,
            $employee->family_name,
        ])));
    }

    private function authorizeSelfService(Request $request): void
    {
        abort_unless(
            $request->user()?->can('self_service.loans'),
            403,
            'لا تملك صلاحية طلب السلف.'
        );
    }

    private function domainError(DomainException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
        ], 422);
    }
}
