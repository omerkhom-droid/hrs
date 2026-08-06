<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained('tenants')
                ->restrictOnDelete();

            $table->boolean('is_platform_admin')
                ->default(false)
                ->after('password');

            $table->boolean('is_active')
                ->default(true)
                ->after('is_platform_admin');

            $table->timestamp('last_login_at')
                ->nullable()
                ->after('is_active');

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'is_active']);
            $table->dropForeign(['tenant_id']);

            $table->dropColumn([
                'tenant_id',
                'is_platform_admin',
                'is_active',
                'last_login_at',
            ]);
        });
    }
};