<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('request_number', 50);

            $table->string('loan_type', 50)
                ->default('salary_advance');

            $table->decimal('requested_amount', 15, 2);
            $table->decimal('approved_amount', 15, 2)
                ->nullable();

            $table->unsignedSmallInteger('installments_count')
                ->default(1);

            $table->decimal('installment_amount', 15, 2)
                ->nullable();

            $table->decimal('paid_amount', 15, 2)
                ->default(0);

            $table->decimal('remaining_amount', 15, 2)
                ->default(0);

            $table->date('first_installment_date')
                ->nullable();

            $table->enum('status', [
                'draft',
                'submitted',
                'approved',
                'rejected',
                'active',
                'completed',
                'cancelled',
            ])->default('draft');

            $table->text('reason');
            $table->text('employee_notes')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('rejected_by')
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

            $table->unique(
                ['tenant_id', 'request_number'],
                'employee_loans_request_unique'
            );

            $table->index(
                ['tenant_id', 'employee_id', 'status'],
                'employee_loans_employee_status_index'
            );

            $table->index(
                ['tenant_id', 'status', 'first_installment_date'],
                'employee_loans_status_date_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};