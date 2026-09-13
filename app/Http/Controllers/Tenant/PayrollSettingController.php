<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StorePayrollSettingRequest;
use App\Models\PayrollSetting;
use App\Services\HR\PayrollBankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollSettingController extends Controller
{
    public function __construct(
        private readonly PayrollBankingService $bankingService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | صفحة الإعدادات
    |--------------------------------------------------------------------------
    */

    public function index(): View
    {
        return view(
            'tenant.payroll.settings.index'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | جلب الإعدادات
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request
    ): JsonResponse {
        $setting = PayrollSetting::query()
            ->where(
                'tenant_id',
                $request->user()->tenant_id
            )
            ->with([
                'createdBy:id,name',
                'updatedBy:id,name',
            ])
            ->first();

        return response()->json([
            'success' => true,

            'setting' =>
                $this->payload($setting),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | حفظ الإعدادات
    |--------------------------------------------------------------------------
    */

    public function store(
        StorePayrollSettingRequest $request
    ): JsonResponse {
        $setting = $this->bankingService
            ->savePayrollSettings(
                $request->user()->tenant,
                $request->user(),
                $request->validated()
            );

        $setting->load([
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'تم حفظ إعدادات الرواتب والبنك بنجاح.',

            'setting' =>
                $this->payload($setting),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | تجهيز البيانات للواجهة
    |--------------------------------------------------------------------------
    */

    private function payload(
        ?PayrollSetting $setting
    ): array {
        if (!$setting) {
            return [
                'exists' =>
                    false,

                'establishment_name' =>
                    auth()->user()
                        ?->tenant
                        ?->name,

                'establishment_number' =>
                    null,

                'unified_number' =>
                    null,

                'commercial_registration_number' =>
                    null,

                'wps_employer_id' =>
                    null,

                'payroll_bank_name' =>
                    null,

                'payroll_bank_code' =>
                    null,

                'payroll_account_holder_name' =>
                    null,

                'masked_payroll_account_number' =>
                    null,

                'masked_payroll_iban' =>
                    null,

                'has_account_number' =>
                    false,

                'has_payroll_iban' =>
                    false,

                'swift_code' =>
                    null,

                'wps_enabled' =>
                    false,

                'default_file_format' =>
                    'bank_csv',

                'salary_payment_day' =>
                    null,

                'payment_reference_prefix' =>
                    null,

                'require_verified_bank_account' =>
                    true,

                'ready_for_bank_file' =>
                    false,

                'ready_for_wps' =>
                    false,

                'readiness_issues' => [
                    'لم يتم حفظ إعدادات الرواتب بعد.',
                ],

                'created_by' =>
                    null,

                'updated_by' =>
                    null,

                'updated_at' =>
                    null,
            ];
        }

        return [
            'exists' =>
                true,

            'id' =>
                $setting->id,

            'establishment_name' =>
                $setting->establishment_name,

            'establishment_number' =>
                $setting->establishment_number,

            'unified_number' =>
                $setting->unified_number,

            'commercial_registration_number' =>
                $setting
                    ->commercial_registration_number,

            'wps_employer_id' =>
                $setting->wps_employer_id,

            'payroll_bank_name' =>
                $setting->payroll_bank_name,

            'payroll_bank_code' =>
                $setting->payroll_bank_code,

            'payroll_account_holder_name' =>
                $setting
                    ->payroll_account_holder_name,

            /*
             * لا نرسل رقم الحساب الحقيقي.
             */
            'masked_payroll_account_number' =>
                $this->maskAccountNumber(
                    $setting->payroll_account_number
                ),

            'masked_payroll_iban' =>
                $setting->masked_payroll_iban,

            'has_account_number' =>
                filled(
                    $setting->payroll_account_number
                ),

            'has_payroll_iban' =>
                filled(
                    $setting->payroll_iban
                ),

            'swift_code' =>
                $setting->payroll_bank_swift,

            'wps_enabled' =>
                $setting->wps_enabled,

            'default_file_format' =>
                $setting->default_file_format,

            'salary_payment_day' =>
                $setting->salary_payment_day,

            'payment_reference_prefix' =>
                $setting->payment_reference_prefix,

            'require_verified_bank_account' =>
                $setting
                    ->require_verified_bank_account,

            'ready_for_bank_file' =>
                $setting->isReadyForBankFile(),

            'ready_for_wps' =>
                $setting->isReadyForWps(),

            'readiness_issues' =>
                $this->readinessIssues(
                    $setting
                ),

            'created_by' =>
                $setting->createdBy?->name,

            'updated_by' =>
                $setting->updatedBy?->name,

            'updated_at' =>
                $setting->updated_at
                    ?->format('Y-m-d H:i:s'),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | إخفاء رقم الحساب
    |--------------------------------------------------------------------------
    */

    private function maskAccountNumber(
        ?string $accountNumber
    ): ?string {
        if (!$accountNumber) {
            return null;
        }

        $accountNumber = preg_replace(
            '/\s+/',
            '',
            $accountNumber
        );

        return '********' . mb_substr(
            $accountNumber,
            -4
        );
    }


    /*
    |--------------------------------------------------------------------------
    | أسباب عدم الجاهزية
    |--------------------------------------------------------------------------
    */

    private function readinessIssues(
        PayrollSetting $setting
    ): array {
        $issues = [];

        if (
            !filled(
                $setting->establishment_name
            )
        ) {
            $issues[] =
                'اسم المنشأة غير مسجل.';
        }

        if (
            !filled(
                $setting->establishment_number
            )
        ) {
            $issues[] =
                'رقم المنشأة غير مسجل.';
        }

        if (
            !filled(
                $setting->payroll_bank_name
            )
        ) {
            $issues[] =
                'اسم بنك الشركة غير مسجل.';
        }

        if (
            !filled(
                $setting->payroll_bank_code
            )
        ) {
            $issues[] =
                'رمز بنك الشركة غير مسجل.';
        }

        if (
            !filled(
                $setting
                    ->payroll_account_holder_name
            )
        ) {
            $issues[] =
                'اسم صاحب حساب الرواتب غير مسجل.';
        }

        if (
            !filled(
                $setting->payroll_iban
            )
        ) {
            $issues[] =
                'IBAN حساب الشركة غير مسجل.';
        }

        if (
            $setting->wps_enabled &&
            !filled(
                $setting->wps_employer_id
            )
        ) {
            $issues[] =
                'معرف المنشأة في حماية الأجور غير مسجل.';
        }

        return $issues;
    }
}