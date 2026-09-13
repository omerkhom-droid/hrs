<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollSetting extends Model
{
    use HasFactory;
    use BelongsToTenant;


    protected $fillable = [
        'tenant_id',

        'establishment_name',
        'establishment_number',
        'unified_number',
        'commercial_registration_number',
        'wps_employer_id',

        'payroll_bank_name',
        'payroll_bank_code',
        'payroll_account_holder_name',
        'payroll_account_number',
        'payroll_iban',
        'payroll_iban_last4',
        'swift_code',

        'wps_enabled',
        'default_file_format',
        'salary_payment_day',
        'payment_reference_prefix',
        'require_verified_bank_account',

        'created_by',
        'updated_by',
        'metadata',
    ];


    protected $hidden = [
        /*
         * منع ظهور البيانات الحساسة عند
         * تحويل الموديل إلى JSON.
         */
        'payroll_account_number',
        'payroll_iban',
    ];


    protected $appends = [
        'masked_payroll_iban',
    ];


    protected function casts(): array
    {
        return [
            /*
             * Laravel يشفر القيم قبل تخزينها
             * ويفك تشفيرها عند قراءتها.
             */
            'payroll_account_number' =>
                'encrypted',

            'payroll_iban' =>
                'encrypted',

            'wps_enabled' =>
                'boolean',

            'salary_payment_day' =>
                'integer',

            'require_verified_bank_account' =>
                'boolean',

            'metadata' =>
                'array',

            'created_at' =>
                'datetime',

            'updated_at' =>
                'datetime',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | العلاقات
    |--------------------------------------------------------------------------
    */

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }


    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | البيانات المقنّعة
    |--------------------------------------------------------------------------
    */

    public function getMaskedPayrollIbanAttribute(): ?string
    {
        if (!$this->payroll_iban_last4) {
            return null;
        }

        return 'SA********************' .
            $this->payroll_iban_last4;
    }


    /*
    |--------------------------------------------------------------------------
    | التحقق من اكتمال بيانات البنك
    |--------------------------------------------------------------------------
    */

    public function hasBankingDetails(): bool
    {
        return
            filled($this->payroll_bank_name) &&
            filled($this->payroll_bank_code) &&
            filled($this->payroll_account_holder_name) &&
            filled($this->payroll_iban);
    }


    /*
    |--------------------------------------------------------------------------
    | التحقق من جاهزية ملف التحويل البنكي
    |--------------------------------------------------------------------------
    */

    public function isReadyForBankFile(): bool
    {
        return
            $this->hasBankingDetails() &&
            filled($this->establishment_name) &&
            filled($this->establishment_number) &&
            filled($this->default_file_format);
    }


    /*
    |--------------------------------------------------------------------------
    | التحقق من جاهزية حماية الأجور
    |--------------------------------------------------------------------------
    */

    public function isReadyForWps(): bool
    {
        return
            $this->wps_enabled &&
            $this->isReadyForBankFile() &&
            filled($this->wps_employer_id);
    }
}