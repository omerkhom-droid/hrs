<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_mobile_devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('device_uuid', 191);
            $table->string('platform', 30);
            $table->string('device_name')->nullable();
            $table->string('device_model')->nullable();
            $table->string('os_version', 100)->nullable();
            $table->string('app_version', 50)->nullable();
            $table->text('push_token')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->string('last_ip', 45)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'user_id', 'device_uuid'],
                'employee_devices_unique'
            );

            $table->index(
                ['tenant_id', 'employee_id', 'is_active'],
                'employee_devices_active_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_mobile_devices');
    }
};
