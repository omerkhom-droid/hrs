<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EmployeeBankAccount extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;


    protected $fillable = [
        'uuid',
        'tenant_id',
        'employee_id',

        'bank_name',
        'bank_code',
        'branch_code',
        'account_holder_name',
        'account_number',
        'iban',
        'iban_hash',
        'iban_last4',
        'swift_code',
        'currency_code',

        'payment_method',
        'is_primary',
        'is_active',
        'is_verified',
        'verified_at',
        'verified_by',

        'created_by',
        'updated_by',
        'metadata',
    ];


    protected $hidden = [
        /*
         * لا تظهر البيانات الحساسة
         * عند تحويل الموديل إلى JSON.
         */
        'account_number',
        'iban',
        'iban_hash',
    ];


    protected $appends = [
        'masked_iban',
        'masked_account_number',
        'payment_method_label',
    ];


    protected static function booted(): void
    {
        static::creating(
            function (
                EmployeeBankAccount $account
            ) {
                if (!$account->uuid) {
                    $account->uuid =
                        (string) Str::uuid();
                }
            }
        );
    }


    protected function casts(): array
    {
        return [
            /*
             * يتم التشفير باستخدام APP_KEY.
             */
            'account_number' =>
                'encrypted',

            'iban' =>
                'encrypted',

            'is_primary' =>
                'boolean',

            'is_active' =>
                'boolean',

            'is_verified' =>
                'boolean',

            'verified_at' =>
                'datetime',

            'metadata' =>
                'array',

            'created_at' =>
                'datetime',

            'updated_at' =>
                'datetime',

            'deleted_at' =>
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


    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class
        );
    }


    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'verified_by'
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

    public function paymentBatchItems(): HasMany
    {
        return $this->hasMany(
            PayrollPaymentBatchItem::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | البيانات المقنّعة
    |--------------------------------------------------------------------------
    */

    public function getMaskedIbanAttribute(): ?string
    {
        if (!$this->iban_last4) {
            return null;
        }

        return 'SA********************' .
            $this->iban_last4;
    }


    public function getMaskedAccountNumberAttribute(): ?string
    {
        if (!$this->account_number) {
            return null;
        }

        $accountNumber = preg_replace(
            '/\s+/',
            '',
            (string) $this->account_number
        );

        $lastFour = mb_substr(
            $accountNumber,
            -4
        );

        return '********' . $lastFour;
    }


    public function getPaymentMethodLabelAttribute(): string
    {
        return match (
            $this->payment_method
        ) {
            'bank_transfer' =>
                'تحويل بنكي',

            'cash' =>
                'نقدي',

            'cheque' =>
                'شيك',

            'wallet' =>
                'محفظة إلكترونية',

            default =>
                'غير محدد',
        };
    }


    /*
    |--------------------------------------------------------------------------
    | التحقق من الجاهزية
    |--------------------------------------------------------------------------
    */

    public function isBankTransfer(): bool
    {
        return $this->payment_method ===
            'bank_transfer';
    }


    public function isReadyForPayroll(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->isBankTransfer()) {
            return true;
        }

        return
            filled($this->bank_name) &&
            filled($this->account_holder_name) &&
            filled($this->iban);
    }


    public function isReadyForWps(): bool
    {
        return
            $this->isReadyForPayroll() &&
            $this->isBankTransfer() &&
            $this->is_verified &&
            filled($this->bank_code);
    }
}