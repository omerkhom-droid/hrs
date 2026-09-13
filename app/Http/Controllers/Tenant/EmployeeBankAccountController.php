<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreEmployeeBankAccountRequest;
use App\Http\Requests\Tenant\UpdateEmployeeBankAccountRequest;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Services\HR\PayrollBankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use LogicException;

class EmployeeBankAccountController extends Controller
{
    public function __construct(
        private readonly PayrollBankingService $bankingService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | صفحة الحسابات البنكية
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): View {
        $this->authorizeManagement(
            $request
        );

        return view(
            'tenant.payroll.bank-accounts.index'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | قائمة الحسابات
    |--------------------------------------------------------------------------
    */

    public function data(
        Request $request
    ): JsonResponse {
        $this->authorizeManagement(
            $request
        );

        $tenantId = (int) $request
            ->user()
            ->tenant_id;

        $search = trim(
            (string) $request->get(
                'search',
                ''
            )
        );

        $verification = trim(
            (string) $request->get(
                'verification',
                ''
            )
        );

        $status = trim(
            (string) $request->get(
                'status',
                ''
            )
        );

        $paymentMethod = trim(
            (string) $request->get(
                'payment_method',
                ''
            )
        );

        $perPage = min(
            max(
                (int) $request->get(
                    'per_page',
                    15
                ),
                10
            ),
            100
        );

        $query = EmployeeBankAccount::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->with([
                'employee.department',
                'employee.jobTitle',
                'verifiedBy:id,name',
                'createdBy:id,name',
                'updatedBy:id,name',
            ])
            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $query->where(
                        function ($query) use (
                            $search
                        ) {
                            $query
                                ->where(
                                    'bank_name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'bank_code',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'iban_last4',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'employee',
                                    function ($employeeQuery) use (
                                        $search
                                    ) {
                                        $employeeQuery->where(
                                            function ($query) use (
                                                $search
                                            ) {
                                                $query
                                                    ->where(
                                                        'employee_number',
                                                        'like',
                                                        "%{$search}%"
                                                    )
                                                    ->orWhere(
                                                        'first_name',
                                                        'like',
                                                        "%{$search}%"
                                                    )
                                                    ->orWhere(
                                                        'father_name',
                                                        'like',
                                                        "%{$search}%"
                                                    )
                                                    ->orWhere(
                                                        'grandfather_name',
                                                        'like',
                                                        "%{$search}%"
                                                    )
                                                    ->orWhere(
                                                        'family_name',
                                                        'like',
                                                        "%{$search}%"
                                                    );
                                            }
                                        );
                                    }
                                );
                        }
                    );
                }
            )
            ->when(
                $verification === 'verified',
                fn ($query) =>
                    $query->where(
                        'is_verified',
                        true
                    )
            )
            ->when(
                $verification === 'unverified',
                fn ($query) =>
                    $query->where(
                        'is_verified',
                        false
                    )
            )
            ->when(
                $status === 'active',
                fn ($query) =>
                    $query->where(
                        'is_active',
                        true
                    )
            )
            ->when(
                $status === 'inactive',
                fn ($query) =>
                    $query->where(
                        'is_active',
                        false
                    )
            )
            ->when(
                in_array(
                    $paymentMethod,
                    [
                        'bank_transfer',
                        'cash',
                        'cheque',
                        'wallet',
                    ],
                    true
                ),
                fn ($query) =>
                    $query->where(
                        'payment_method',
                        $paymentMethod
                    )
            )
            ->latest('id');

        $paginator = $query->paginate(
            $perPage
        );

        $paginator->getCollection()
            ->transform(
                fn (
                    EmployeeBankAccount $account
                ) => $this->payload($account)
            );

        return response()->json(
            $paginator
        );
    }


    /*
    |--------------------------------------------------------------------------
    | خيارات النموذج
    |--------------------------------------------------------------------------
    */

    public function options(
        Request $request
    ): JsonResponse {
        $this->authorizeManagement(
            $request
        );

        $tenantId = (int) $request
            ->user()
            ->tenant_id;

        $employees = Employee::query()
            ->where(
                'tenant_id',
                $tenantId
            )
            ->whereNotIn(
                'employment_status',
                [
                    'terminated',
                ]
            )
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
            ->map(
                function (Employee $employee) {
                    return [
                        'id' =>
                            $employee->id,

                        'employee_number' =>
                            $employee->employee_number,

                        'full_name' =>
                            $this->employeeName(
                                $employee
                            ),
                    ];
                }
            )
            ->values();

        return response()->json([
            'employees' =>
                $employees,

            'currencies' => [
                [
                    'code' => 'SAR',
                    'name' => 'ريال سعودي',
                ],
                [
                    'code' => 'AED',
                    'name' => 'درهم إماراتي',
                ],
                [
                    'code' => 'KWD',
                    'name' => 'دينار كويتي',
                ],
                [
                    'code' => 'BHD',
                    'name' => 'دينار بحريني',
                ],
                [
                    'code' => 'OMR',
                    'name' => 'ريال عماني',
                ],
                [
                    'code' => 'QAR',
                    'name' => 'ريال قطري',
                ],
                [
                    'code' => 'EGP',
                    'name' => 'جنيه مصري',
                ],
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | إنشاء حساب
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreEmployeeBankAccountRequest $request
    ): JsonResponse {
        $employee = Employee::query()
            ->where(
                'tenant_id',
                $request->user()->tenant_id
            )
            ->findOrFail(
                $request->integer(
                    'employee_id'
                )
            );

        $account = $this->bankingService
            ->createBankAccount(
                $employee,
                $request->user(),
                $request->validated()
            );

        return response()->json([
            'success' => true,

            'message' =>
                'تم إنشاء الحساب البنكي بنجاح.',

            'account' =>
                $this->payload($account),
        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | عرض حساب
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        EmployeeBankAccount $bankAccount
    ): JsonResponse {
        $this->authorizeManagement(
            $request
        );

        $this->assertTenantAccount(
            $request,
            $bankAccount
        );

        $bankAccount->load([
            'employee.department',
            'employee.jobTitle',
            'verifiedBy:id,name',
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);

        return response()->json([
            'success' => true,

            'account' =>
                $this->payload($bankAccount),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | تعديل الحساب
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateEmployeeBankAccountRequest $request,
        EmployeeBankAccount $bankAccount
    ): JsonResponse {
        $this->assertTenantAccount(
            $request,
            $bankAccount
        );

        $account = $this->bankingService
            ->updateBankAccount(
                $bankAccount,
                $request->user(),
                $request->validated()
            );

        return response()->json([
            'success' => true,

            'message' =>
                'تم تحديث الحساب البنكي بنجاح.',

            'account' =>
                $this->payload($account),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | اعتماد الحساب
    |--------------------------------------------------------------------------
    */

    public function verify(
        Request $request,
        EmployeeBankAccount $bankAccount
    ): JsonResponse {
        $this->authorizeVerification(
            $request
        );

        $this->assertTenantAccount(
            $request,
            $bankAccount
        );

        try {
            $account = $this->bankingService
                ->verifyBankAccount(
                    $bankAccount,
                    $request->user()
                );
        } catch (LogicException $exception) {
            return response()->json([
                'success' => false,

                'message' =>
                    $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,

            'message' =>
                'تم اعتماد الحساب البنكي بنجاح.',

            'account' =>
                $this->payload($account),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | إلغاء الاعتماد
    |--------------------------------------------------------------------------
    */

    public function unverify(
        Request $request,
        EmployeeBankAccount $bankAccount
    ): JsonResponse {
        $this->authorizeVerification(
            $request
        );

        $this->assertTenantAccount(
            $request,
            $bankAccount
        );

        $account = $this->bankingService
            ->unverifyBankAccount(
                $bankAccount,
                $request->user()
            );

        return response()->json([
            'success' => true,

            'message' =>
                'تم إلغاء اعتماد الحساب البنكي.',

            'account' =>
                $this->payload($account),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | حذف الحساب
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        EmployeeBankAccount $bankAccount
    ): JsonResponse {
        $this->authorizeManagement(
            $request
        );

        $this->assertTenantAccount(
            $request,
            $bankAccount
        );

        $this->bankingService
            ->deleteBankAccount(
                $bankAccount,
                $request->user()
            );

        return response()->json([
            'success' => true,

            'message' =>
                'تم حذف الحساب البنكي بنجاح.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | تجهيز بيانات الحساب
    |--------------------------------------------------------------------------
    */

    private function payload(
        EmployeeBankAccount $account
    ): array {
        $employee =
            $account->employee;

        return [
            'id' =>
                $account->id,

            'uuid' =>
                $account->uuid,

            'employee' => [
                'id' =>
                    $employee?->id,

                'employee_number' =>
                    $employee?->employee_number,

                'full_name' =>
                    $employee
                        ? $this->employeeName(
                            $employee
                        )
                        : '-',

                'department_name' =>
                    $employee
                        ?->department
                        ?->name,

                'job_title_name' =>
                    $employee
                        ?->jobTitle
                        ?->name,
            ],

            'bank_name' =>
                $account->bank_name,

            'bank_code' =>
                $account->bank_code,

            'branch_code' =>
                $account->branch_code,

            'account_holder_name' =>
                $account->account_holder_name,

            /*
             * لا نرسل البيانات الحقيقية.
             */
            'masked_account_number' =>
                $account
                    ->masked_account_number,

            'masked_iban' =>
                $account->masked_iban,

            'has_account_number' =>
                filled(
                    $account->account_number
                ),

            'has_iban' =>
                filled(
                    $account->iban
                ),

            'swift_code' =>
                $account->swift_code,

            'currency_code' =>
                $account->currency_code,

            'payment_method' =>
                $account->payment_method,

            'payment_method_label' =>
                $account
                    ->payment_method_label,

            'is_primary' =>
                $account->is_primary,

            'is_active' =>
                $account->is_active,

            'is_verified' =>
                $account->is_verified,

            'ready_for_payroll' =>
                $account
                    ->isReadyForPayroll(),

            'ready_for_wps' =>
                $account
                    ->isReadyForWps(),

            'verified_by' =>
                $account
                    ->verifiedBy
                    ?->name,

            'verified_at' =>
                $account
                    ->verified_at
                    ?->format(
                        'Y-m-d H:i:s'
                    ),

            'created_by' =>
                $account
                    ->createdBy
                    ?->name,

            'updated_by' =>
                $account
                    ->updatedBy
                    ?->name,

            'created_at' =>
                $account
                    ->created_at
                    ?->format(
                        'Y-m-d H:i:s'
                    ),

            'updated_at' =>
                $account
                    ->updated_at
                    ?->format(
                        'Y-m-d H:i:s'
                    ),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | اسم الموظف
    |--------------------------------------------------------------------------
    */

    private function employeeName(
        Employee $employee
    ): string {
        return trim(
            collect([
                $employee->first_name,
                $employee->father_name,
                $employee->grandfather_name,
                $employee->family_name,
            ])
                ->filter()
                ->implode(' ')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | صلاحية الإدارة
    |--------------------------------------------------------------------------
    */

    private function authorizeManagement(
        Request $request
    ): void {
        $user = $request->user();

        if (
            !$user ||
            !(
                $user->can(
                    'employees.update'
                ) ||
                $user->can(
                    'payroll.manage'
                )
            )
        ) {
            abort(403);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | صلاحية الاعتماد
    |--------------------------------------------------------------------------
    */

    private function authorizeVerification(
        Request $request
    ): void {
        $user = $request->user();

        if (
            !$user ||
            !(
                $user->can(
                    'payroll.approve'
                ) ||
                $user->can(
                    'settings.update'
                )
            )
        ) {
            abort(403);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | عزل بيانات الشركات
    |--------------------------------------------------------------------------
    */

    private function assertTenantAccount(
        Request $request,
        EmployeeBankAccount $account
    ): void {
        if (
            (int) $account->tenant_id !==
            (int) $request
                ->user()
                ->tenant_id
        ) {
            abort(404);
        }
    }
}