<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            /*
             * يستخدم عند تجديد العقد مع الاحتفاظ بالتسلسل التاريخي.
             */
            $table->foreignId('renewed_from_id')
                ->nullable()
                ->constrained('employee_contracts')
                ->nullOnDelete();

            $table->string('contract_number', 50);

            $table->enum('contract_type', [
                'indefinite',
                'fixed_term',
                'temporary',
                'seasonal',
                'part_time',
                'training',
            ])->default('fixed_term');

            $table->enum('status', [
                'draft',
                'active',
                'suspended',
                'expired',
                'terminated',
                'cancelled',
            ])->default('draft');

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('probation_end_date')->nullable();

            /*
             * بيانات الراتب الثابتة وقت توقيع العقد.
             */
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('housing_allowance', 15, 2)->default(0);
            $table->decimal('transport_allowance', 15, 2)->default(0);
            $table->decimal('other_allowances', 15, 2)->default(0);
            $table->char('currency_code', 3)->default('SAR');

            $table->enum('pay_frequency', [
                'monthly',
                'daily',
                'hourly',
            ])->default('monthly');

            $table->decimal('working_hours_per_day', 4, 2)->default(8);
            $table->unsignedTinyInteger('working_days_per_week')->default(5);
            $table->unsignedSmallInteger('annual_leave_days')->default(21);
            $table->unsignedSmallInteger('notice_period_days')->default(30);

            $table->boolean('auto_renew')->default(false);
            $table->unsignedSmallInteger('renewal_notice_days')->default(30);

            $table->timestamp('signed_at')->nullable();
            $table->timestamp('activated_at')->nullable();

            $table->foreignId('activated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();

            $table->foreignId('terminated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->longText('terms')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'tenant_id',
                'contract_number',
            ]);

            $table->index([
                'tenant_id',
                'employee_id',
                'status',
            ]);

            $table->index([
                'tenant_id',
                'status',
                'end_date',
            ]);

            $table->index([
                'tenant_id',
                'start_date',
                'end_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_contracts');
    }
};