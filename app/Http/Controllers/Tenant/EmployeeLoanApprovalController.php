<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ApproveEmployeeLoanRequest;
use App\Http\Requests\Tenant\RejectEmployeeLoanRequest;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Services\HR\EmployeeLoanService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeLoanApprovalController extends Controller
{
    public function __construct(
        private readonly EmployeeLoanService $loanService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | صفحة اعتماد السلف
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $this->authorizeApproval($request);

        return view(
            'tenant.payroll.employee-loans.approvals'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | الطلبات المرسلة للاعتماد
    |--------------------------------------------------------------------------
    */

    public function data(Request $request): JsonResponse
    {
        $this->authorizeApproval($request);

        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'nullable',
                'in:submitted,approved,rejected,active,completed,cancelled',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'between:10,100',
            ],
        ]);

        $status = $validated['status']
            ?? 'submitted';

        $loans = EmployeeLoan::query()
            ->where(
                'tenant_id',
                $request->user()->tenant_id
            )
            ->with([
                'employee',
                'creator:id,name',
                'approver:id,name',
                'rejecter:id,name',
            ])
            ->search($validated['search'] ?? null)
            ->status($status)
            ->latest('submitted_at')
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
    | تفاصيل طلب الاعتماد
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        EmployeeLoan $employeeLoan
    ): JsonResponse {
        $this->authorizeApproval($request);

        $loan = $this->resolveLoan(
            $request,
            $employeeLoan
        );

        $loan->load([
            'employee',
            'installments',
            'creator:id,name',
            'approver:id,name',
            'rejecter:id,name',
            'canceller:id,name',
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
    | اعتماد السلفة
    |--------------------------------------------------------------------------
    */

    public function approve(
        ApproveEmployeeLoanRequest $request,
        EmployeeLoan $employeeLoan
    ): JsonResponse {
        $loan = $this->resolveLoan(
            $request,
            $employeeLoan
        );

        try {
            $loan = $this->loanService->approve(
                $loan,
                $request->user(),
                $request->validated()
            );
        } catch (DomainException $exception) {
            return $this->domainError($exception);
        }

        return response()->json([
            'success' => true,

            'message' =>
                'تم اعتماد السلفة وإنشاء الأقساط بنجاح.',

            'data' => $this->loanPayload(
                $loan,
                true
            ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | رفض السلفة
    |--------------------------------------------------------------------------
    */

    public function reject(
        RejectEmployeeLoanRequest $request,
        EmployeeLoan $employeeLoan
    ): JsonResponse {
        $loan = $this->resolveLoan(
            $request,
            $employeeLoan
        );

        try {
            $loan = $this->loanService->reject(
                $loan,
                $request->user(),
                $request->string(
                    'rejection_reason'
                )->toString()
            );
        } catch (DomainException $exception) {
            return $this->domainError($exception);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم رفض طلب السلفة.',
            'data' => $this->loanPayload($loan),
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

            'submitted_at' =>
                $loan->submitted_at
                    ?->toIso8601String(),

            'approved_at' =>
                $loan->approved_at
                    ?->toIso8601String(),

            'rejected_at' =>
                $loan->rejected_at
                    ?->toIso8601String(),

            'employee' => [
                'id' => $loan->employee->id,

                'employee_number' =>
                    $loan->employee
                        ->employee_number,

                'name' => $this->employeeName(
                    $loan->employee
                ),
            ],

            'can_approve' =>
                $loan->status === 'submitted',

            'can_reject' =>
                $loan->status === 'submitted',

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

    private function authorizeApproval(
        Request $request
    ): void {
        abort_unless(
            $request->user()?->can(
                'loans.approve'
            ),
            403,
            'لا تملك صلاحية اعتماد السلف.'
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