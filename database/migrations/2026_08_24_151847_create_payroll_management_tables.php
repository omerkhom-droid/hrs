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
        | مكونات الرواتب
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'salary_components',
            function (Blueprint $table) {
                $table->id();

                $table->uuid('uuid')
                    ->unique();

                $table->foreignId('tenant_id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();

                $table->string(
                    'code',
                    50
                );

                $table->string('name');

                $table->string('name_en')
                    ->nullable();

                /*
                 * earning   = استحقاق
                 * deduction = استقطاع
                 */
                $table->enum(
                    'type',
                    [
                        'earning',
                        'deduction',
                    ]
                );

                $table->enum(
                    'category',
                    [
                        'basic_salary',
                        'allowance',
                        'bonus',
                        'commission',
                        'overtime',
                        'reimbursement',
                        'tax',
                        'insurance',
                        'loan',
                        'absence',
                        'penalty',
                        'other',
                    ]
                )->default('other');

                $table->enum(
                    'calculation_method',
                    [
                        'fixed',
                        'percentage',
                        'formula',
                        'quantity_rate',
                    ]
                )->default('fixed');

                /*
                 * المكون الأساسي لحساب النسبة.
                 * مثال: بدل السكن 25% من الأساسي.
                 */
                $table->foreignId(
                    'percentage_base_component_id'
                )
                    ->nullable()
                    ->constrained(
                        'salary_components'
                    )
                    ->nullOnDelete();

                $table->decimal(
                    'default_amount',
                    18,
                    4
                )->default(0);

                $table->decimal(
                    'default_percentage',
                    9,
                    4
                )->nullable();

                $table->decimal(
                    'default_rate',
                    18,
                    4
                )->nullable();

                $table->text('formula')
                    ->nullable();

                $table->boolean('is_taxable')
                    ->default(false);

                $table->boolean(
                    'is_subject_to_insurance'
                )->default(false);

                $table->boolean(
                    'is_included_in_overtime_base'
                )->default(false);

                $table->boolean('is_proratable')
                    ->default(true);

                $table->boolean('is_recurring')
                    ->default(true);

                $table->boolean('requires_input')
                    ->default(false);

                $table->boolean(
                    'affects_net_salary'
                )->default(true);

                $table->boolean(
                    'is_system'
                )->default(false);

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->unsignedInteger(
                    'sort_order'
                )->default(0);

                $table->json('metadata')
                    ->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    [
                        'tenant_id',
                        'code',
                    ],
                    'salary_components_tenant_code_unique'
                );

                $table->index(
                    [
                        'tenant_id',
                        'type',
                        'is_active',
                    ],
                    'salary_components_lookup_index'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | هياكل رواتب الموظفين
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'employee_salary_structures',
            function (Blueprint $table) {
                $table->id();

                $table->uuid('uuid')
                    ->unique();

                $table->foreignId('tenant_id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();

                $table->foreignId('employee_id')
                    ->constrained('employees')
                    ->restrictOnDelete();

                $table->unsignedInteger(
                    'version'
                )->default(1);

                $table->date(
                    'effective_from'
                );

                $table->date('effective_to')
                    ->nullable();

                $table->string(
                    'currency_code',
                    3
                )->default('SAR');

                $table->decimal(
                    'basic_salary',
                    18,
                    4
                )->default(0);

                $table->decimal(
                    'total_earnings',
                    18,
                    4
                )->default(0);

                $table->decimal(
                    'total_deductions',
                    18,
                    4
                )->default(0);

                $table->decimal(
                    'net_salary',
                    18,
                    4
                )->default(0);

                $table->enum(
                    'status',
                    [
                        'draft',
                        'active',
                        'expired',
                        'cancelled',
                    ]
                )->default('draft');

                $table->text('notes')
                    ->nullable();

                $table->foreignId(
                    'approved_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp(
                    'approved_at'
                )->nullable();

                $table->foreignId(
                    'created_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->json('metadata')
                    ->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    [
                        'tenant_id',
                        'employee_id',
                        'version',
                    ],
                    'salary_structures_version_unique'
                );

                $table->index(
                    [
                        'tenant_id',
                        'employee_id',
                        'status',
                    ],
                    'salary_structures_employee_index'
                );

                $table->index(
                    [
                        'effective_from',
                        'effective_to',
                    ],
                    'salary_structures_effective_index'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | مكونات هيكل راتب الموظف
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'employee_salary_components',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('tenant_id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();

                $table->foreignId(
                    'salary_structure_id'
                )
                    ->constrained(
                        'employee_salary_structures'
                    )
                    ->cascadeOnDelete();

                $table->foreignId(
                    'salary_component_id'
                )
                    ->constrained(
                        'salary_components'
                    )
                    ->restrictOnDelete();

                $table->decimal(
                    'amount',
                    18,
                    4
                )->default(0);

                $table->decimal(
                    'percentage',
                    9,
                    4
                )->nullable();

                $table->decimal(
                    'rate',
                    18,
                    4
                )->nullable();

                $table->decimal(
                    'quantity',
                    12,
                    4
                )->nullable();

                $table->text('formula')
                    ->nullable();

                $table->boolean(
                    'is_active'
                )->default(true);

                $table->json('metadata')
                    ->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    [
                        'salary_structure_id',
                        'salary_component_id',
                    ],
                    'employee_salary_component_unique'
                );

                $table->index(
                    [
                        'tenant_id',
                        'salary_component_id',
                    ],
                    'employee_salary_component_lookup'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | فترات الرواتب
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'payroll_periods',
            function (Blueprint $table) {
                $table->id();

                $table->uuid('uuid')
                    ->unique();

                $table->foreignId('tenant_id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();

                $table->string(
                    'code',
                    50
                );

                $table->string('name');

                $table->unsignedSmallInteger(
                    'year'
                );

                $table->unsignedTinyInteger(
                    'month'
                );

                $table->date('start_date');
                $table->date('end_date');

                $table->date(
                    'payment_date'
                )->nullable();

                $table->enum(
                    'status',
                    [
                        'draft',
                        'open',
                        'processing',
                        'review',
                        'approved',
                        'paid',
                        'closed',
                        'cancelled',
                    ]
                )->default('draft');

                $table->boolean(
                    'is_locked'
                )->default(false);

                $table->timestamp(
                    'locked_at'
                )->nullable();

                $table->foreignId(
                    'locked_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId(
                    'created_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->json('metadata')
                    ->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    [
                        'tenant_id',
                        'code',
                    ],
                    'payroll_period_tenant_code_unique'
                );

                $table->unique(
                    [
                        'tenant_id',
                        'year',
                        'month',
                    ],
                    'payroll_period_tenant_month_unique'
                );

                $table->index(
                    [
                        'tenant_id',
                        'status',
                    ],
                    'payroll_period_status_index'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | مسيرات الرواتب
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'payroll_runs',
            function (Blueprint $table) {
                $table->id();

                $table->uuid('uuid')
                    ->unique();

                $table->foreignId('tenant_id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();

                $table->foreignId(
                    'payroll_period_id'
                )
                    ->constrained(
                        'payroll_periods'
                    )
                    ->restrictOnDelete();

                $table->string(
                    'run_number',
                    50
                );

                $table->enum(
                    'type',
                    [
                        'regular',
                        'off_cycle',
                        'final_settlement',
                    ]
                )->default('regular');

                $table->enum(
                    'status',
                    [
                        'draft',
                        'calculating',
                        'calculated',
                        'review',
                        'approved',
                        'paid',
                        'cancelled',
                    ]
                )->default('draft');

                $table->string(
                    'currency_code',
                    3
                )->default('SAR');

                $table->unsignedInteger(
                    'employee_count'
                )->default(0);

                $table->decimal(
                    'total_basic_salary',
                    18,
                    4
                )->default(0);

                $table->decimal(
                    'total_earnings',
                    18,
                    4
                )->default(0);

                $table->decimal(
                    'total_deductions',
                    18,
                    4
                )->default(0);

                $table->decimal(
                    'total_net_salary',
                    18,
                    4
                )->default(0);

                $table->timestamp(
                    'calculated_at'
                )->nullable();

                $table->foreignId(
                    'calculated_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp(
                    'approved_at'
                )->nullable();

                $table->foreignId(
                    'approved_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp(
                    'paid_at'
                )->nullable();

                $table->foreignId(
                    'paid_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId(
                    'created_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->text('notes')
                    ->nullable();

                $table->json('metadata')
                    ->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    [
                        'tenant_id',
                        'run_number',
                    ],
                    'payroll_run_number_unique'
                );

                $table->index(
                    [
                        'tenant_id',
                        'payroll_period_id',
                        'status',
                    ],
                    'payroll_run_period_status_index'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | نتائج رواتب الموظفين داخل المسير
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'payroll_run_items',
            function (Blueprint $table) {
                $table->id();

                $table->uuid('uuid')
                    ->unique();

                $table->foreignId('tenant_id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();

                $table->foreignId(
                    'payroll_run_id'
                )
                    ->constrained(
                        'payroll_runs'
                    )
                    ->cascadeOnDelete();

                $table->foreignId(
                    'employee_id'
                )
                    ->constrained('employees')
                    ->restrictOnDelete();

                $table->foreignId(
                    'salary_structure_id'
                )
                    ->nullable()
                    ->constrained(
                        'employee_salary_structures'
                    )
                    ->nullOnDelete();

                $table->string(
                    'currency_code',
                    3
                )->default('SAR');

                $table->decimal(
                    'basic_salary',
                    18,
                    4
                )->default(0);

                $table->decimal(
                    'gross_salary',
                    18,
                    4
                )->default(0);

                $table->decimal(
                    'total_earnings',
                    18,
                    4
                )->default(0);

                $table->decimal(
                    'total_deductions',
                    18,
                    4
                )->default(0);

                $table->decimal(
                    'net_salary',
                    18,
                    4
                )->default(0);

                $table->decimal(
                    'scheduled_work_days',
                    8,
                    2
                )->default(0);

                $table->decimal(
                    'actual_work_days',
                    8,
                    2
                )->default(0);

                $table->decimal(
                    'absent_days',
                    8,
                    2
                )->default(0);

                $table->decimal(
                    'paid_leave_days',
                    8,
                    2
                )->default(0);

                $table->decimal(
                    'unpaid_leave_days',
                    8,
                    2
                )->default(0);

                $table->unsignedInteger(
                    'overtime_minutes'
                )->default(0);

                $table->enum(
                    'status',
                    [
                        'pending',
                        'calculated',
                        'exception',
                        'approved',
                        'paid',
                    ]
                )->default('pending');

                $table->json(
                    'calculation_snapshot'
                )->nullable();

                $table->json('errors')
                    ->nullable();

                $table->json('metadata')
                    ->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    [
                        'payroll_run_id',
                        'employee_id',
                    ],
                    'payroll_run_employee_unique'
                );

                $table->index(
                    [
                        'tenant_id',
                        'employee_id',
                        'status',
                    ],
                    'payroll_item_employee_status_index'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | تفاصيل الاستحقاقات والاستقطاعات
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'payroll_run_item_components',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('tenant_id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();

                $table->foreignId(
                    'payroll_item_id'
                )
                    ->constrained(
                        'payroll_run_items'
                    )
                    ->cascadeOnDelete();

                $table->foreignId(
                    'salary_component_id'
                )
                    ->nullable()
                    ->constrained(
                        'salary_components'
                    )
                    ->nullOnDelete();

                /*
                 * نخزن الكود والاسم كنسخة تاريخية
                 * حتى لو تغير اسم المكون لاحقًا.
                 */
                $table->string(
                    'component_code',
                    50
                );

                $table->string(
                    'component_name'
                );

                $table->enum(
                    'type',
                    [
                        'earning',
                        'deduction',
                    ]
                );

                $table->string(
                    'category',
                    50
                )->nullable();

                $table->enum(
                    'source',
                    [
                        'salary_structure',
                        'attendance',
                        'overtime',
                        'leave',
                        'manual',
                        'system',
                    ]
                )->default('salary_structure');

                $table->decimal(
                    'quantity',
                    12,
                    4
                )->nullable();

                $table->decimal(
                    'rate',
                    18,
                    4
                )->nullable();

                $table->decimal(
                    'percentage',
                    9,
                    4
                )->nullable();

                $table->decimal(
                    'amount',
                    18,
                    4
                )->default(0);

                $table->boolean(
                    'is_taxable'
                )->default(false);

                $table->boolean(
                    'is_subject_to_insurance'
                )->default(false);

                $table->nullableMorphs(
                    'reference'
                );

                $table->json('metadata')
                    ->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'payroll_item_id',
                        'type',
                    ],
                    'payroll_item_component_type_index'
                );

                $table->index(
                    [
                        'tenant_id',
                        'salary_component_id',
                    ],
                    'payroll_component_lookup_index'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | التسويات اليدوية
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'payroll_adjustments',
            function (Blueprint $table) {
                $table->id();

                $table->uuid('uuid')
                    ->unique();

                $table->foreignId('tenant_id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();

                $table->foreignId(
                    'employee_id'
                )
                    ->constrained('employees')
                    ->restrictOnDelete();

                $table->foreignId(
                    'payroll_period_id'
                )
                    ->nullable()
                    ->constrained(
                        'payroll_periods'
                    )
                    ->nullOnDelete();

                $table->foreignId(
                    'salary_component_id'
                )
                    ->nullable()
                    ->constrained(
                        'salary_components'
                    )
                    ->nullOnDelete();

                $table->foreignId(
                    'applied_payroll_item_id'
                )
                    ->nullable()
                    ->constrained(
                        'payroll_run_items'
                    )
                    ->nullOnDelete();

                $table->string(
                    'adjustment_number',
                    50
                );

                $table->enum(
                    'type',
                    [
                        'earning',
                        'deduction',
                    ]
                );

                $table->decimal(
                    'amount',
                    18,
                    4
                );

                $table->string(
                    'currency_code',
                    3
                )->default('SAR');

                $table->date(
                    'effective_date'
                );

                $table->enum(
                    'status',
                    [
                        'draft',
                        'pending',
                        'approved',
                        'applied',
                        'rejected',
                        'cancelled',
                    ]
                )->default('draft');

                $table->string('reason');

                $table->text('notes')
                    ->nullable();

                $table->foreignId(
                    'approved_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp(
                    'approved_at'
                )->nullable();

                $table->foreignId(
                    'created_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->json('metadata')
                    ->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    [
                        'tenant_id',
                        'adjustment_number',
                    ],
                    'payroll_adjustment_number_unique'
                );

                $table->index(
                    [
                        'tenant_id',
                        'employee_id',
                        'status',
                    ],
                    'payroll_adjustment_employee_index'
                );

                $table->index(
                    [
                        'payroll_period_id',
                        'status',
                    ],
                    'payroll_adjustment_period_index'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'payroll_adjustments'
        );

        Schema::dropIfExists(
            'payroll_run_item_components'
        );

        Schema::dropIfExists(
            'payroll_run_items'
        );

        Schema::dropIfExists(
            'payroll_runs'
        );

        Schema::dropIfExists(
            'payroll_periods'
        );

        Schema::dropIfExists(
            'employee_salary_components'
        );

        Schema::dropIfExists(
            'employee_salary_structures'
        );

        Schema::dropIfExists(
            'salary_components'
        );
    }
};