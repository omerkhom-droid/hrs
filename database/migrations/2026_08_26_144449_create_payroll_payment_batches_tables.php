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
        | دفعات تحويل الرواتب
        |--------------------------------------------------------------------------
        */

        Schema::create('payroll_payment_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('payroll_run_id')
                ->constrained('payroll_runs')
                ->restrictOnDelete();

            /*
             * رقم داخلي فريد للدفعة داخل الشركة.
             */
            $table->string('batch_number', 100);

            /*
             * المرجع الذي يظهر في ملف التحويل البنكي.
             */
            $table->string('payment_reference', 150);

            $table->string('name');
            $table->date('payment_date');

            $table->char(
                'currency_code',
                3
            )->default('SAR');

            /*
             * نوع الملف المطلوب.
             * bank_csv صيغة داخلية عامة وليست صيغة رسمية لبنك محدد.
             */
            $table->enum('file_format', [
                'bank_csv',
                'csv',
                'txt',
                'sif',
            ])->default('bank_csv');

            /*
             * دورة حياة دفعة الرواتب:
             *
             * draft      مسودة
             * validated  تم فحصها
             * generated  تم إنشاء الملف
             * submitted  تم إرسالها للبنك
             * processing قيد المعالجة
             * completed  اكتملت
             * partially_completed اكتملت جزئيًا
             * failed     فشلت
             * cancelled  ملغاة
             */
            $table->enum('status', [
                'draft',
                'validated',
                'generated',
                'submitted',
                'processing',
                'completed',
                'partially_completed',
                'failed',
                'cancelled',
            ])->default('draft');

            /*
             * إجماليات الدفعة.
             */
            $table->unsignedInteger('employees_count')
                ->default(0);

            $table->unsignedInteger('ready_employees_count')
                ->default(0);

            $table->unsignedInteger('exception_employees_count')
                ->default(0);

            $table->decimal('total_amount', 18, 2)
                ->default(0);

            $table->decimal('successful_amount', 18, 2)
                ->default(0);

            $table->decimal('failed_amount', 18, 2)
                ->default(0);

            /*
             * بيانات البنك المرسِل وقت إنشاء الدفعة.
             * تحفظ كلقطة مستقلة حتى لا تتغير الدفعة القديمة
             * عند تعديل إعدادات الشركة لاحقًا.
             */
            $table->string('source_bank_name')->nullable();
            $table->string('source_bank_code', 50)->nullable();

            $table->text('source_account_number')->nullable();
            $table->string('source_account_last_four', 4)->nullable();

            $table->text('source_iban')->nullable();
            $table->string('source_iban_last_four', 4)->nullable();

            $table->string('source_swift_code', 20)->nullable();

            /*
             * معلومات الملف الناتج.
             */
            $table->string('file_disk', 50)
                ->nullable();

            $table->string('file_path')
                ->nullable();

            $table->string('file_name')
                ->nullable();

            $table->string('file_mime_type', 100)
                ->nullable();

            $table->unsignedBigInteger('file_size')
                ->nullable();

            $table->string('file_checksum', 128)
                ->nullable();

            $table->timestamp('validated_at')
                ->nullable();

            $table->foreignId('validated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('generated_at')
                ->nullable();

            $table->foreignId('generated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('submitted_at')
                ->nullable();

            $table->foreignId('submitted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('completed_at')
                ->nullable();

            $table->foreignId('completed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('cancelled_at')
                ->nullable();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('cancellation_reason')
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->json('metadata')
                ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'tenant_id',
                'batch_number',
            ], 'payroll_payment_batches_number_unique');

            $table->unique([
                'tenant_id',
                'payment_reference',
            ], 'payroll_payment_batches_reference_unique');

            $table->index([
                'tenant_id',
                'payroll_run_id',
                'status',
            ], 'payroll_payment_batches_run_status_index');

            $table->index([
                'tenant_id',
                'payment_date',
            ], 'payroll_payment_batches_date_index');
        });


        /*
        |--------------------------------------------------------------------------
        | موظفو دفعة التحويل
        |--------------------------------------------------------------------------
        */

        Schema::create('payroll_payment_batch_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('payroll_payment_batch_id')
                ->constrained('payroll_payment_batches')
                ->cascadeOnDelete();

            $table->foreignId('payroll_run_item_id')
                ->constrained('payroll_run_items')
                ->restrictOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->foreignId('employee_bank_account_id')
                ->nullable()
                ->constrained('employee_bank_accounts')
                ->nullOnDelete();

            /*
             * بيانات الموظف وقت إنشاء الدفعة.
             */
            $table->string('employee_number', 100);
            $table->string('employee_name');

            /*
             * قيمة صافي الراتب المطلوب تحويلها.
             */
            $table->decimal('amount', 18, 2);

            $table->char(
                'currency_code',
                3
            )->default('SAR');

            $table->enum('payment_method', [
                'bank_transfer',
                'cash',
                'cheque',
                'wallet',
            ])->default('bank_transfer');

            /*
             * لقطة من حساب الموظف وقت إنشاء الدفعة.
             * الحقول الحساسة ستُشفّر داخل Model.
             */
            $table->string('bank_name')->nullable();
            $table->string('bank_code', 50)->nullable();
            $table->string('bank_branch_code', 50)->nullable();

            $table->string('account_holder_name')->nullable();

            $table->text('account_number')->nullable();
            $table->string('account_number_last_four', 4)->nullable();

            $table->text('iban')->nullable();
            $table->string('iban_hash', 64)->nullable();
            $table->string('iban_last_four', 4)->nullable();

            $table->string('swift_code', 20)->nullable();

            /*
             * حالة كل موظف داخل الدفعة.
             *
             * pending    في انتظار الفحص
             * ready      جاهز للتحويل
             * exception  توجد مشكلة في بياناته
             * submitted  أرسل للبنك
             * paid       تم التحويل
             * failed     فشل التحويل
             * excluded   مستبعد من الدفعة
             * cancelled  ملغى
             */
            $table->enum('status', [
                'pending',
                'ready',
                'exception',
                'submitted',
                'paid',
                'failed',
                'excluded',
                'cancelled',
            ])->default('pending');

            /*
             * سبب عدم الجاهزية أو فشل التحويل.
             */
            $table->string('exception_code', 100)
                ->nullable();

            $table->text('exception_message')
                ->nullable();

            /*
             * مرجع البنك بعد إرسال التحويل.
             */
            $table->string('bank_transaction_reference', 150)
                ->nullable();

            $table->timestamp('submitted_at')
                ->nullable();

            $table->timestamp('paid_at')
                ->nullable();

            $table->timestamp('failed_at')
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->json('metadata')
                ->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
             * يمنع تكرار نفس موظف المسير داخل الدفعة نفسها.
             */
            $table->unique([
                'payroll_payment_batch_id',
                'payroll_run_item_id',
            ], 'payroll_payment_batch_run_item_unique');

            $table->index([
                'tenant_id',
                'employee_id',
                'status',
            ], 'payroll_payment_batch_employee_status_index');

            $table->index([
                'tenant_id',
                'payroll_payment_batch_id',
                'status',
            ], 'payroll_payment_batch_item_status_index');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'payroll_payment_batch_items'
        );

        Schema::dropIfExists(
            'payroll_payment_batches'
        );
    }
};