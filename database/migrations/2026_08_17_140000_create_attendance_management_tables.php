<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->string('code', 50);
            $table->string('name');
            $table->string('timezone')->default('Asia/Riyadh');

            $table->unsignedSmallInteger('late_grace_minutes')->default(10);
            $table->unsignedSmallInteger('early_leave_grace_minutes')->default(5);
            $table->unsignedSmallInteger('early_check_in_minutes')->default(120);
            $table->unsignedSmallInteger('late_check_out_minutes')->default(240);
            $table->unsignedSmallInteger('overtime_after_minutes')->default(0);

            $table->enum('rounding_rule', [
                'none',
                'nearest_5',
                'nearest_10',
                'nearest_15',
            ])->default('none');

            $table->boolean('allow_web')->default(true);
            $table->boolean('allow_mobile')->default(true);
            $table->boolean('require_geofence')->default(false);
            $table->boolean('allow_outside_geofence')->default(false);
            $table->boolean('require_photo')->default(false);
            $table->boolean('auto_check_out')->default(false);

            $table->json('weekend_days')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_default', 'is_active']);
        });

        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('attendance_policy_id')
                ->nullable()
                ->constrained('attendance_policies')
                ->nullOnDelete();

            $table->string('code', 50);
            $table->string('name');
            $table->string('name_en')->nullable();

            $table->enum('shift_type', [
                'regular',
                'flexible',
                'night',
            ])->default('regular');

            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('crosses_midnight')->default(false);
            $table->unsignedSmallInteger('break_minutes')->default(60);
            $table->unsignedSmallInteger('working_minutes')->default(480);
            $table->json('work_days')->nullable();

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index([
                'tenant_id',
                'attendance_policy_id',
                'is_active',
            ]);
        });

        Schema::create('employee_shift_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->foreignId('work_shift_id')
                ->constrained('work_shifts')
                ->cascadeOnDelete();

            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'tenant_id',
                'employee_id',
                'effective_from',
                'effective_to',
            ], 'employee_shift_assignment_period_index');
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->foreignId('work_shift_id')
                ->nullable()
                ->constrained('work_shifts')
                ->nullOnDelete();

            $table->foreignId('work_location_id')
                ->nullable()
                ->constrained('work_locations')
                ->nullOnDelete();

            $table->date('attendance_date');
            $table->string('timezone')->default('Asia/Riyadh');

            $table->dateTime('scheduled_check_in_at')->nullable();
            $table->dateTime('scheduled_check_out_at')->nullable();
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();

            $table->enum('check_in_source', [
                'web',
                'mobile',
                'manual',
                'device',
                'api',
            ])->nullable();

            $table->enum('check_out_source', [
                'web',
                'mobile',
                'manual',
                'device',
                'api',
            ])->nullable();

            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->unsignedInteger('check_in_distance')->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->unsignedInteger('check_out_distance')->nullable();

            $table->string('check_in_ip', 45)->nullable();
            $table->string('check_out_ip', 45)->nullable();
            $table->string('check_in_device')->nullable();
            $table->string('check_out_device')->nullable();
            $table->string('check_in_photo_path', 1000)->nullable();
            $table->string('check_out_photo_path', 1000)->nullable();

            $table->enum('status', [
                'present',
                'late',
                'absent',
                'on_leave',
                'holiday',
                'remote',
                'incomplete',
            ])->default('incomplete');

            $table->unsignedInteger('work_minutes')->default(0);
            $table->unsignedInteger('break_minutes')->default(0);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_leave_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);

            $table->enum('approval_status', [
                'pending',
                'approved',
                'rejected',
            ])->default('pending');

            $table->timestamp('approved_at')->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'tenant_id',
                'employee_id',
                'attendance_date',
            ], 'attendance_employee_date_unique');

            $table->index([
                'tenant_id',
                'attendance_date',
                'status',
            ]);

            $table->index([
                'tenant_id',
                'approval_status',
                'attendance_date',
            ], 'attendance_approval_date_index');
        });

        Schema::create('attendance_breaks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('attendance_record_id')
                ->constrained('attendance_records')
                ->cascadeOnDelete();

            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->unsignedInteger('duration_minutes')->default(0);

            $table->enum('source', [
                'web',
                'mobile',
                'manual',
                'device',
                'api',
            ])->default('manual');

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'attendance_record_id']);
        });

        Schema::create('attendance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('attendance_record_id')
                ->constrained('attendance_records')
                ->cascadeOnDelete();

            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->json('original_values');
            $table->json('requested_values');
            $table->text('reason');

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
            ])->default('pending');

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index([
                'tenant_id',
                'status',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_adjustments');
        Schema::dropIfExists('attendance_breaks');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('employee_shift_assignments');
        Schema::dropIfExists('work_shifts');
        Schema::dropIfExists('attendance_policies');
    }
};
