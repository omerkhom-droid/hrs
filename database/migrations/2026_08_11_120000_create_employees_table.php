<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            /*
            |------------------------------------------------------------------
            | الشركة وحساب الدخول
            |------------------------------------------------------------------
            */

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('employee_number', 50);
            $table->string('attendance_code', 50)->nullable();

            /*
            |------------------------------------------------------------------
            | الهيكل التنظيمي
            |------------------------------------------------------------------
            */

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->foreignId('job_title_id')
                ->nullable()
                ->constrained('job_titles')
                ->nullOnDelete();

            $table->foreignId('work_location_id')
                ->nullable()
                ->constrained('work_locations')
                ->nullOnDelete();

            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            /*
            |------------------------------------------------------------------
            | الاسم والهوية الشخصية
            |------------------------------------------------------------------
            */

            $table->string('first_name');
            $table->string('father_name')->nullable();
            $table->string('grandfather_name')->nullable();
            $table->string('family_name');
            $table->string('name_en')->nullable();

            $table->enum('identity_type', [
                'national_id',
                'iqama',
                'passport',
                'gcc',
                'other',
            ])->nullable();

            $table->string('identity_number', 100)->nullable();
            $table->date('identity_expiry_date')->nullable();
            $table->string('nationality_code', 2)->nullable();

            $table->enum('gender', [
                'male',
                'female',
            ])->nullable();

            $table->date('birth_date')->nullable();

            $table->enum('marital_status', [
                'single',
                'married',
                'divorced',
                'widowed',
            ])->nullable();

            /*
            |------------------------------------------------------------------
            | بيانات التواصل
            |------------------------------------------------------------------
            */

            $table->string('personal_email')->nullable();
            $table->string('work_email')->nullable();
            $table->string('personal_phone', 50)->nullable();
            $table->string('work_phone', 50)->nullable();
            $table->string('country_code', 2)->default('SA');
            $table->string('city')->nullable();
            $table->text('address')->nullable();

            /*
            |------------------------------------------------------------------
            | جهة الاتصال في حالات الطوارئ
            |------------------------------------------------------------------
            */

            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relation')->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();

            /*
            |------------------------------------------------------------------
            | بيانات التوظيف
            |------------------------------------------------------------------
            */

            $table->enum('employment_type', [
                'full_time',
                'part_time',
                'contract',
                'temporary',
                'intern',
                'consultant',
            ])->default('full_time');

            $table->enum('employment_status', [
                'draft',
                'probation',
                'active',
                'on_leave',
                'suspended',
                'terminated',
            ])->default('draft');

            $table->date('hire_date')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();

            /*
            |------------------------------------------------------------------
            | بيانات إضافية
            |------------------------------------------------------------------
            */

            $table->string('timezone')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
            |------------------------------------------------------------------
            | القيود والفهارس
            |------------------------------------------------------------------
            */

            $table->unique([
                'tenant_id',
                'employee_number',
            ]);

            $table->unique([
                'tenant_id',
                'attendance_code',
            ]);

            $table->unique([
                'tenant_id',
                'identity_number',
            ]);

            $table->unique([
                'tenant_id',
                'work_email',
            ]);

            $table->unique([
                'tenant_id',
                'user_id',
            ]);

            $table->index([
                'tenant_id',
                'employment_status',
            ]);

            $table->index([
                'tenant_id',
                'branch_id',
                'department_id',
            ]);

            $table->index([
                'tenant_id',
                'job_title_id',
            ]);

            $table->index([
                'tenant_id',
                'manager_id',
            ]);

            $table->index([
                'tenant_id',
                'hire_date',
                'termination_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};