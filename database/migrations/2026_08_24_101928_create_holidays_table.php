<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'holidays',
            function (Blueprint $table) {
                $table->id();

                $table->uuid('uuid')
                    ->unique();

                $table->foreignId('tenant_id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();

                /*
                 * null تعني أن العطلة تطبق
                 * على جميع فروع الشركة.
                 */
                $table->foreignId('branch_id')
                    ->nullable()
                    ->constrained('branches')
                    ->nullOnDelete();

                $table->string(
                    'code',
                    50
                );

                $table->string('name');

                $table->string('name_en')
                    ->nullable();

                /*
                 * public
                 * company
                 * national
                 * religious
                 * other
                 */
                $table->string(
                    'type',
                    30
                )->default('public');

                $table->date('start_date');

                $table->date('end_date');

                /*
                 * هل العطلة مدفوعة الأجر؟
                 */
                $table->boolean('is_paid')
                    ->default(true);

                /*
                 * عدم احتساب أيام العطلة
                 * من رصيد الإجازة.
                 */
                $table->boolean(
                    'exclude_from_leave_days'
                )->default(true);

                /*
                 * اعتبار اليوم عطلة داخل
                 * سجلات الحضور والانصراف.
                 */
                $table->boolean(
                    'affects_attendance'
                )->default(true);

                /*
                 * تكرار العطلة سنويًا
                 * بنفس اليوم والشهر.
                 */
                $table->boolean('is_recurring')
                    ->default(false);

                $table->boolean('is_active')
                    ->default(true);

                $table->json('metadata')
                    ->nullable();

                $table->timestamps();

                $table->softDeletes();

                $table->unique([
                    'tenant_id',
                    'code',
                ]);

                $table->index([
                    'tenant_id',
                    'start_date',
                    'end_date',
                    'is_active',
                ], 'holidays_tenant_dates_index');

                $table->index([
                    'tenant_id',
                    'branch_id',
                    'is_active',
                ], 'holidays_branch_index');
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'holidays'
        );
    }
};