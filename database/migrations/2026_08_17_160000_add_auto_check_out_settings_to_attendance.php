<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_policies', function (Blueprint $table) {
            $table->unsignedSmallInteger(
                'auto_check_out_after_minutes'
            )
                ->default(60)
                ->after('auto_check_out');
        });

        DB::statement("
            ALTER TABLE attendance_records
            MODIFY check_out_source ENUM(
                'web',
                'mobile',
                'manual',
                'device',
                'api',
                'system'
            ) NULL
        ");

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->index([
                'check_out_at',
                'deleted_at',
                'scheduled_check_out_at',
                'tenant_id',
            ], 'attendance_auto_check_out_index');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropIndex('attendance_auto_check_out_index');
        });

        DB::table('attendance_records')
            ->where('check_out_source', 'system')
            ->update(['check_out_source' => 'api']);

        DB::statement("
            ALTER TABLE attendance_records
            MODIFY check_out_source ENUM(
                'web',
                'mobile',
                'manual',
                'device',
                'api'
            ) NULL
        ");

        Schema::table('attendance_policies', function (Blueprint $table) {
            $table->dropColumn('auto_check_out_after_minutes');
        });
    }
};
