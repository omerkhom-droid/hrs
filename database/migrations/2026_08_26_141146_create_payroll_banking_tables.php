<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | إعدادات الرواتب والبنك لكل شركة
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'payroll_settings',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('tenant_id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();

                /*
                 * لا يسمح بأكثر من إعداد واحد لكل شركة.
                 */
                $table->unique('tenant_id');


                /*
                |--------------------------------------------------------------------------
                | بيانات المنشأة
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'establishment_name'
                )->nullable();

                $table->string(
                    'establishment_number',
                    100
                )->nullable();

                $table->string(
                    'unified_number',
                    100
                )->nullable();

                $table->string(
                    'commercial_registration_number',
                    100
                )->nullable();

                $table->string(
                    'wps_employer_id',
                    100
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | حساب الشركة الذي تُصرف منه الرواتب
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'payroll_bank_name'
                )->nullable();

                $table->string(
                    'payroll_bank_code',
                    50
                )->nullable();

                $table->string(
                    'payroll_account_holder_name'
                )->nullable();

                /*
                 * ستكون مشفرة من خلال Model Cast.
                 */
                $table->text(
                    'payroll_account_number'
                )->nullable();

                $table->text(
                    'payroll_iban'
                )->nullable();

                /*
                 * آخر أربعة أرقام للعرض في الواجهة.
                 */
                $table->string(
                    'payroll_iban_last4',
                    4
                )->nullable();

                $table->string(
                    'swift_code',
                    30
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | إعدادات ملف الرواتب
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'wps_enabled'
                )->default(false);

                /*
                 * لا نستخدم enum حتى نستطيع إضافة
                 * تنسيقات خاصة بكل بنك لاحقًا.
                 */
                $table->string(
                    'default_file_format',
                    50
                )->default('bank_csv');

                $table->unsignedTinyInteger(
                    'salary_payment_day'
                )->nullable();

                $table->string(
                    'payment_reference_prefix',
                    50
                )->nullable();

                $table->boolean(
                    'require_verified_bank_account'
                )->default(true);


                /*
                |--------------------------------------------------------------------------
                | التتبع
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'created_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId(
                    'updated_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->json(
                    'metadata'
                )->nullable();

                $table->timestamps();
            }
        );


        /*
        |--------------------------------------------------------------------------
        | الحسابات البنكية للموظفين
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'employee_bank_accounts',
            function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();

                $table->foreignId('tenant_id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();

                $table->foreignId('employee_id')
                    ->constrained('employees')
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | بيانات البنك
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'bank_name'
                );

                $table->string(
                    'bank_code',
                    50
                )->nullable();

                $table->string(
                    'branch_code',
                    50
                )->nullable();

                $table->string(
                    'account_holder_name'
                );

                /*
                 * يتم تشفير القيمتين في الموديل.
                 */
                $table->text(
                    'account_number'
                )->nullable();

                $table->text(
                    'iban'
                )->nullable();

                /*
                 * يستخدم للتحقق من عدم تكرار IBAN
                 * دون تخزين IBAN بصورة مكشوفة.
                 */
                $table->char(
                    'iban_hash',
                    64
                )->nullable();

                $table->string(
                    'iban_last4',
                    4
                )->nullable();

                $table->string(
                    'swift_code',
                    30
                )->nullable();

                $table->char(
                    'currency_code',
                    3
                )->default('SAR');


                /*
                |--------------------------------------------------------------------------
                | طريقة الصرف والحالة
                |--------------------------------------------------------------------------
                */

                $table->enum(
                    'payment_method',
                    [
                        'bank_transfer',
                        'cash',
                        'cheque',
                        'wallet',
                    ]
                )->default('bank_transfer');

                $table->boolean(
                    'is_primary'
                )->default(true);

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->boolean(
                    'is_verified'
                )->default(false);

                $table->timestamp(
                    'verified_at'
                )->nullable();

                $table->foreignId(
                    'verified_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();


                /*
                |--------------------------------------------------------------------------
                | التتبع
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'created_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId(
                    'updated_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->json(
                    'metadata'
                )->nullable();

                $table->timestamps();
                $table->softDeletes();


                /*
                |--------------------------------------------------------------------------
                | الفهارس
                |--------------------------------------------------------------------------
                */

                $table->index([
                    'tenant_id',
                    'employee_id',
                    'is_active',
                ]);

                $table->index([
                    'tenant_id',
                    'employee_id',
                    'is_primary',
                ]);

                $table->index([
                    'tenant_id',
                    'is_verified',
                ]);

                $table->unique(
                    [
                        'tenant_id',
                        'employee_id',
                        'iban_hash',
                    ],
                    'employee_bank_iban_unique'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'employee_bank_accounts'
        );

        Schema::dropIfExists(
            'payroll_settings'
        );
    }
};