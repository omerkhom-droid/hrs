<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->string('code', 50);
            $table->string('name');
            $table->string('name_en')->nullable();

            $table->enum('unit', [
                'day',
                'hour',
            ])->default('day');

            $table->enum('payment_type', [
                'paid',
                'unpaid',
                'partially_paid',
            ])->default('paid');

            $table->unsignedTinyInteger('paid_percentage')
                ->default(100);

            $table->decimal('default_entitlement', 8, 2)
                ->default(0);

            $table->enum('accrual_method', [
                'none',
                'annual',
                'monthly',
            ])->default('annual');

            $table->boolean('requires_balance')->default(true);
            $table->boolean('allow_negative_balance')->default(false);
            $table->boolean('allow_during_probation')->default(false);
            $table->boolean('allow_half_day')->default(true);
            $table->boolean('requires_attachment')->default(false);

            $table->unsignedSmallInteger('minimum_notice_days')
                ->default(0);

            $table->unsignedSmallInteger('maximum_consecutive_days')
                ->nullable();

            $table->boolean('allow_carry_forward')->default(false);

            $table->decimal('maximum_carry_forward', 8, 2)
                ->default(0);

            $table->enum('gender', [
                'all',
                'male',
                'female',
            ])->default('all');

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'tenant_id',
                'code',
            ]);

            $table->index([
                'tenant_id',
                'is_active',
                'sort_order',
            ], 'leave_types_tenant_active_sort_idx');
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('year');

            $table->decimal('opening_balance', 8, 2)->default(0);
            $table->decimal('accrued_balance', 8, 2)->default(0);
            $table->decimal('carried_forward', 8, 2)->default(0);
            $table->decimal('adjustment_balance', 8, 2)->default(0);
            $table->decimal('used_balance', 8, 2)->default(0);
            $table->decimal('pending_balance', 8, 2)->default(0);
            $table->decimal('available_balance', 8, 2)->default(0);

            $table->date('calculated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique([
                'tenant_id',
                'employee_id',
                'leave_type_id',
                'year',
            ], 'leave_balances_employee_type_year_unique');

            $table->index([
                'tenant_id',
                'year',
            ], 'leave_balances_tenant_year_idx');
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->restrictOnDelete();

            $table->foreignId('replacement_employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->date('start_date');
            $table->date('end_date');
            $table->date('return_date')->nullable();

            $table->enum('start_session', [
                'full_day',
                'first_half',
                'second_half',
            ])->default('full_day');

            $table->enum('end_session', [
                'full_day',
                'first_half',
                'second_half',
            ])->default('full_day');

            $table->decimal('requested_amount', 8, 2);
            $table->decimal('approved_amount', 8, 2)->nullable();

            $table->enum('status', [
                'draft',
                'pending',
                'approved',
                'rejected',
                'cancelled',
            ])->default('pending');

            $table->text('reason');
            $table->text('handover_notes')->nullable();
            $table->string('contact_during_leave')->nullable();
            $table->string('attachment_path', 1000)->nullable();
            $table->text('decision_notes')->nullable();

            $table->unsignedTinyInteger('current_approval_level')
                ->default(1);

            $table->unsignedTinyInteger('required_approval_levels')
                ->default(1);

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'tenant_id',
                'status',
                'start_date',
                'end_date',
            ], 'leave_requests_tenant_status_dates_idx');

            $table->index([
                'tenant_id',
                'employee_id',
                'start_date',
            ], 'leave_requests_employee_date_idx');
        });

        Schema::create('leave_request_days', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('leave_request_id')
                ->constrained('leave_requests')
                ->cascadeOnDelete();

            $table->date('leave_date');
            $table->decimal('amount', 5, 2)->default(1);
            $table->boolean('is_working_day')->default(true);
            $table->boolean('is_paid')->default(true);
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique([
                'leave_request_id',
                'leave_date',
            ], 'leave_request_days_request_date_unique');

            $table->index([
                'tenant_id',
                'leave_date',
            ], 'leave_request_days_tenant_date_idx');
        });

        Schema::create(
            'leave_balance_transactions',
            function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();

                $table->foreignId('tenant_id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();

                $table->foreignId('leave_balance_id')
                    ->constrained('leave_balances')
                    ->cascadeOnDelete();

                $table->foreignId('leave_request_id')
                    ->nullable()
                    ->constrained('leave_requests')
                    ->nullOnDelete();

                $table->enum('type', [
                    'opening',
                    'accrual',
                    'carry_forward',
                    'usage',
                    'reversal',
                    'adjustment',
                    'expiry',
                ]);

                $table->decimal('amount', 8, 2);
                $table->decimal('balance_after', 8, 2);
                $table->date('effective_date');
                $table->text('notes')->nullable();

                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index([
                    'tenant_id',
                    'leave_balance_id',
                    'effective_date',
                ], 'leave_balance_transactions_date_idx');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balance_transactions');
        Schema::dropIfExists('leave_request_days');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
    }
};