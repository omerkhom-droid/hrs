<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CancelPayrollPaymentBatchRequest;
use App\Http\Requests\Tenant\ExcludePayrollPaymentBatchItemRequest;
use App\Http\Requests\Tenant\StorePayrollPaymentBatchRequest;
use App\Models\PayrollPaymentBatch;
use App\Models\PayrollPaymentBatchItem;
use App\Models\PayrollRun;
use App\Services\HR\PayrollPaymentBatchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Services\HR\PayrollBankFileService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollPaymentBatchController extends Controller
{
    public function __construct(
        private readonly PayrollPaymentBatchService $service,
        private readonly PayrollBankFileService $bankFileService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | قائمة دفعات تحويل الرواتب
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $user = Auth::user();

        abort_unless(
            $user?->can('payroll.view'),
            403
        );

        $tenantId = (int) $user->tenant_id;

        $filters = $request->validate([
            'status' => [
                'nullable',
                'string',
                'in:draft,validated,submitted,processing,partially_paid,paid,failed,cancelled',
            ],

            'payroll_run_id' => [
                'nullable',
                'integer',
            ],

            'date_from' => [
                'nullable',
                'date',
            ],

            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],

            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $batches = PayrollPaymentBatch::query()
            ->where('tenant_id', $tenantId)
            ->with([
                'payrollRun',
                'createdBy:id,name',
                'validatedBy:id,name',
            ])
            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) =>
                    $query->where('status', $status)
            )
            ->when(
                $filters['payroll_run_id'] ?? null,
                fn ($query, $payrollRunId) =>
                    $query->where(
                        'payroll_run_id',
                        $payrollRunId
                    )
            )
            ->when(
                $filters['date_from'] ?? null,
                fn ($query, $date) =>
                    $query->whereDate(
                        'payment_date',
                        '>=',
                        $date
                    )
            )
            ->when(
                $filters['date_to'] ?? null,
                fn ($query, $date) =>
                    $query->whereDate(
                        'payment_date',
                        '<=',
                        $date
                    )
            )
            ->when(
                $filters['search'] ?? null,
                function ($query, $search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery
                            ->where(
                                'batch_number',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'payment_reference',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'name',
                                'like',
                                "%{$search}%"
                            );
                    });
                }
            )
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view(
            'tenant.payroll.payment-batches.index',
            compact('batches', 'filters')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | صفحة إنشاء دفعة
    |--------------------------------------------------------------------------
    */

    public function create(): View
    {
        $user = Auth::user();

        abort_unless(
            $user?->can('payroll.manage'),
            403
        );

        $tenantId = (int) $user->tenant_id;

        /*
         * نظهر المسيرات المعتمدة التي تحتوي عناصر قابلة للتحويل،
         * ولا ترتبط بدفعة فعالة.
         */
        $payrollRuns = PayrollRun::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->whereHas(
                'items',
                fn ($query) =>
                    $query->where('net_salary', '>', 0)
            )
            ->whereDoesntHave(
                'paymentBatches',
                function ($query) {
                    $query->whereNotIn('status', [
                        'cancelled',
                        'failed',
                    ]);
                }
            )
            ->with([
                'period',
            ])
            ->latest('id')
            ->get();

        return view(
            'tenant.payroll.payment-batches.create',
            compact('payrollRuns')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | حفظ دفعة جديدة
    |--------------------------------------------------------------------------
    */

    public function store(
        StorePayrollPaymentBatchRequest $request
    ): RedirectResponse {
        $user = $request->user();

        $payrollRun = PayrollRun::query()
            ->where(
                'tenant_id',
                $user->tenant_id
            )
            ->findOrFail(
                $request->integer('payroll_run_id')
            );

        $batch = $this->service->createFromPayrollRun(
            $payrollRun,
            $user,
            $request->validated()
        );

        return redirect()
            ->route(
                'app.payroll-payment-batches.show',
                $batch
            )
            ->with(
                'success',
                'تم إنشاء دفعة تحويل الرواتب وفحص حسابات الموظفين بنجاح.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | تفاصيل الدفعة
    |--------------------------------------------------------------------------
    */

    public function show(
        PayrollPaymentBatch $payrollPaymentBatch
    ): View {
        $user = Auth::user();

        abort_unless(
            $user?->can('payroll.view'),
            403
        );

        $this->ensureBatchBelongsToTenant(
            $payrollPaymentBatch,
            (int) $user->tenant_id
        );

        $payrollPaymentBatch->load([
            'payrollRun.period',
            'createdBy:id,name',
            'updatedBy:id,name',
            'validatedBy:id,name',
            'cancelledBy:id,name',

            'items' => fn ($query) =>
                $query->orderBy('employee_name'),

            'items.employee:id,employee_number,first_name,father_name,grandfather_name,family_name',
        ]);

        return view(
            'tenant.payroll.payment-batches.show',
            [
                'batch' => $payrollPaymentBatch,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | إعادة فحص الدفعة
    |--------------------------------------------------------------------------
    */

    public function validateBatch(
        PayrollPaymentBatch $payrollPaymentBatch
    ): RedirectResponse {
        $user = Auth::user();

        abort_unless(
            $user?->can('payroll.manage'),
            403
        );

        $this->ensureBatchBelongsToTenant(
            $payrollPaymentBatch,
            (int) $user->tenant_id
        );

        $batch = $this->service->validateBatch(
            $payrollPaymentBatch,
            $user
        );

        $message = $batch->status === 'validated'
            ? 'تم فحص الدفعة، وجميع الموظفين جاهزون للتحويل.'
            : 'تم فحص الدفعة، ولكن توجد حسابات تحتاج إلى معالجة.';

        return redirect()
            ->route(
                'app.payroll-payment-batches.show',
                $batch
            )
            ->with(
                $batch->status === 'validated'
                    ? 'success'
                    : 'warning',
                $message
            );
    }


    /*
    |--------------------------------------------------------------------------
    | إلغاء الدفعة
    |--------------------------------------------------------------------------
    */

    public function cancel(
        CancelPayrollPaymentBatchRequest $request,
        PayrollPaymentBatch $payrollPaymentBatch
    ): RedirectResponse {
        $user = $request->user();

        $this->ensureBatchBelongsToTenant(
            $payrollPaymentBatch,
            (int) $user->tenant_id
        );

        $batch = $this->service->cancelBatch(
            $payrollPaymentBatch,
            $user,
            $request->validated('reason')
        );

        return redirect()
            ->route(
                'app.payroll-payment-batches.show',
                $batch
            )
            ->with(
                'success',
                'تم إلغاء دفعة تحويل الرواتب.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | استبعاد موظف
    |--------------------------------------------------------------------------
    */

    public function excludeItem(
        ExcludePayrollPaymentBatchItemRequest $request,
        PayrollPaymentBatch $payrollPaymentBatch,
        PayrollPaymentBatchItem $item
    ): RedirectResponse {
        $user = $request->user();

        $this->ensureItemBelongsToBatch(
            $payrollPaymentBatch,
            $item,
            (int) $user->tenant_id
        );

        $this->service->excludeItem(
            $item,
            $user,
            $request->validated('reason')
        );

        return redirect()
            ->route(
                'app.payroll-payment-batches.show',
                $payrollPaymentBatch
            )
            ->with(
                'success',
                'تم استبعاد الموظف من دفعة التحويل.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | إعادة موظف مستبعد
    |--------------------------------------------------------------------------
    */

    public function restoreItem(
        PayrollPaymentBatch $payrollPaymentBatch,
        PayrollPaymentBatchItem $item
    ): RedirectResponse {
        $user = Auth::user();

        abort_unless(
            $user?->can('payroll.manage'),
            403
        );

        $this->ensureItemBelongsToBatch(
            $payrollPaymentBatch,
            $item,
            (int) $user->tenant_id
        );

        $restoredItem = $this->service->restoreItem(
            $item,
            $user
        );

        $message = $restoredItem->status === 'ready'
            ? 'تمت إعادة الموظف، وحسابه جاهز للتحويل.'
            : 'تمت إعادة الموظف، ولكن بيانات حسابه تحتاج إلى معالجة.';

        return redirect()
            ->route(
                'app.payroll-payment-batches.show',
                $payrollPaymentBatch
            )
            ->with(
                $restoredItem->status === 'ready'
                    ? 'success'
                    : 'warning',
                $message
            );
    }


    /*
    |--------------------------------------------------------------------------
    | بيانات مختصرة للاستخدام عبر AJAX
    |--------------------------------------------------------------------------
    */

    public function summary(
        PayrollPaymentBatch $payrollPaymentBatch
    ): JsonResponse {
        $user = Auth::user();

        abort_unless(
            $user?->can('payroll.view'),
            403
        );

        $this->ensureBatchBelongsToTenant(
            $payrollPaymentBatch,
            (int) $user->tenant_id
        );

        return response()->json([
            'id' =>
                $payrollPaymentBatch->id,

            'batch_number' =>
                $payrollPaymentBatch->batch_number,

            'name' =>
                $payrollPaymentBatch->name,

            'status' =>
                $payrollPaymentBatch->status,

            'status_label' =>
                $payrollPaymentBatch->status_label,

            'employees_count' =>
                $payrollPaymentBatch->employees_count,

            'ready_employees_count' =>
                $payrollPaymentBatch->ready_employees_count,

            'exception_employees_count' =>
                $payrollPaymentBatch->exception_employees_count,

            'total_amount' =>
                $payrollPaymentBatch->total_amount,

            'currency_code' =>
                $payrollPaymentBatch->currency_code,

            'can_validate' =>
                $payrollPaymentBatch->can_validate,

            'can_cancel' =>
                $payrollPaymentBatch->can_cancel,
        ]);
    }


    public function generateFile(
        PayrollPaymentBatch $payrollPaymentBatch
    ): RedirectResponse {
        $user = Auth::user();

        abort_unless(
            $user?->can('payroll.process'),
            403
        );

        $this->ensureBatchBelongsToTenant(
            $payrollPaymentBatch,
            (int) $user->tenant_id
        );

        $batch = $this->bankFileService->generate(
            $payrollPaymentBatch,
            $user
        );

        return redirect()
            ->route(
                'app.payroll-payment-batches.show',
                $batch
            )
            ->with(
                'success',
                'تم إنشاء ملف التحويل البنكي بنجاح.'
            );
    }


    public function downloadFile(
        PayrollPaymentBatch $payrollPaymentBatch
    ): StreamedResponse {
        $user = Auth::user();

        abort_unless(
            $user?->can('payroll.process'),
            403
        );

        $this->ensureBatchBelongsToTenant(
            $payrollPaymentBatch,
            (int) $user->tenant_id
        );

        abort_unless(
            $payrollPaymentBatch->hasGeneratedFile(),
            404
        );

        $disk =
            $payrollPaymentBatch->file_disk
            ?: 'local';

        abort_unless(
            Storage::disk($disk)->exists(
                $payrollPaymentBatch->file_path
            ),
            404,
            'ملف التحويل غير موجود.'
        );

        return Storage::disk($disk)->download(
            $payrollPaymentBatch->file_path,
            $payrollPaymentBatch->file_name,
            [
                'Content-Type' =>
                    $payrollPaymentBatch->file_mime_type
                    ?: 'text/csv; charset=UTF-8',

                'X-Content-Type-Options' =>
                    'nosniff',
            ]
        );
    }
    
    /*
    |--------------------------------------------------------------------------
    | حماية بيانات الشركات
    |--------------------------------------------------------------------------
    */

    private function ensureBatchBelongsToTenant(
        PayrollPaymentBatch $batch,
        int $tenantId
    ): void {
        abort_unless(
            (int) $batch->tenant_id === $tenantId,
            404
        );
    }


    private function ensureItemBelongsToBatch(
        PayrollPaymentBatch $batch,
        PayrollPaymentBatchItem $item,
        int $tenantId
    ): void {
        abort_unless(
            (int) $batch->tenant_id === $tenantId
            && (int) $item->tenant_id === $tenantId
            && (int) $item->payroll_payment_batch_id
                === (int) $batch->id,
            404
        );
    }
}