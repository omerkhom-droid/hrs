<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'employee_loan_installments',
            function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();

                $table->foreignId('tenant_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('employee_loan_id')
                    ->constrained('employee_loans')
                    ->cascadeOnDelete();

                $table->foreignId('employee_id')
                    ->constrained()
                    ->restrictOnDelete();

                $table->unsignedSmallInteger(
                    'installment_number'
                );

                $table->date('due_date');

                $table->decimal('amount', 15, 2);

                $table->decimal('paid_amount', 15, 2)
                    ->default(0);

                $table->decimal('remaining_amount', 15, 2);

                $table->enum('status', [
                    'pending',
                    'partially_paid',
                    'deducted',
                    'paid',
                    'postponed',
                    'cancelled',
                ])->default('pending');

                /*
                 * سنربطهما بجداول الرواتب بعد التأكد
                 * من أسماء الجداول والموديلات الموجودة.
                 */
                $table->foreignId('payroll_run_id')
                    ->nullable()
                    ->constrained('payroll_runs')
                    ->nullOnDelete();

                $table->foreignId('payroll_run_item_id')
                    ->nullable()
                    ->constrained('payroll_run_items')
                    ->nullOnDelete();

                $table->date('deducted_at')->nullable();
                $table->timestamp('paid_at')->nullable();

                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'employee_loan_id',
                        'installment_number',
                    ],
                    'employee_loan_installment_unique'
                );

                $table->index(
                    [
                        'tenant_id',
                        'employee_id',
                        'status',
                        'due_date',
                    ],
                    'loan_installments_due_index'
                );

                $table->index(
                    [
                        'payroll_run_id',
                        'payroll_run_item_id',
                    ],
                    'loan_installments_payroll_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'employee_loan_installments'
        );
    }
};