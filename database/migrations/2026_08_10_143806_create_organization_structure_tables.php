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
        | الفروع
        |--------------------------------------------------------------------------
        */

        Schema::create('branches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->string('code', 50);
            $table->string('name');
            $table->string('name_en')->nullable();

            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();

            $table->string('country_code', 2)->default('SA');
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('timezone')->default('Asia/Riyadh');

            $table->boolean('is_main')->default(false);
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
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | الإدارات والأقسام
        |--------------------------------------------------------------------------
        */

        Schema::create('departments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->string('code', 50);
            $table->string('name');
            $table->string('name_en')->nullable();

            $table->text('description')->nullable();

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
                'branch_id',
                'parent_id',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | المسميات الوظيفية
        |--------------------------------------------------------------------------
        */

        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->string('code', 50);
            $table->string('name');
            $table->string('name_en')->nullable();

            $table->text('description')->nullable();

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
                'department_id',
                'is_active',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | مواقع العمل
        |--------------------------------------------------------------------------
        */

        Schema::create('work_locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();

            $table->string('code', 50);
            $table->string('name');
            $table->string('name_en')->nullable();

            $table->enum('type', [
                'office',
                'site',
                'warehouse',
                'remote',
                'other',
            ])->default('office');

            $table->string('country_code', 2)->default('SA');
            $table->string('city')->nullable();
            $table->text('address')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            /*
             * نطاق تسجيل الحضور بالمتر.
             */
            $table->unsignedInteger('attendance_radius')->default(100);

            $table->string('timezone')->default('Asia/Riyadh');
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
                'branch_id',
                'is_active',
            ]);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('work_locations');
        Schema::dropIfExists('job_titles');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('branches');
    }
};