<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->foreignId('attendance_record_id')
                ->nullable()
                ->constrained('attendance_records')
                ->nullOnDelete();

            $table->date('overtime_date');
            $table->string('timezone')->default('Asia/Riyadh');
            $table->dateTime('planned_start_at');
            $table->dateTime('planned_end_at');

            $table->unsignedSmallInteger('requested_minutes');
            $table->unsignedSmallInteger('actual_minutes')->default(0);
            $table->unsignedSmallInteger('approved_minutes')->nullable();

            $table->enum('type', [
                'regular_day',
                'rest_day',
                'holiday',
                'emergency',
            ])->default('regular_day');

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'cancelled',
                'completed',
            ])->default('pending');

            $table->text('reason');
            $table->text('decision_notes')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
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
                'overtime_date',
                'status',
            ], 'overtime_tenant_date_status_index');

            $table->index([
                'tenant_id',
                'employee_id',
                'overtime_date',
            ], 'overtime_employee_date_index');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->unsignedInteger('approved_overtime_minutes')
                ->default(0)
                ->after('overtime_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn('approved_overtime_minutes');
        });

        Schema::dropIfExists('overtime_requests');
    }
};
