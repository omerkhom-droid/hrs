<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_policies', function (Blueprint $table) {
            $table->enum('approval_mode', [
                'manual',
                'auto_clean',
            ])
                ->default('auto_clean')
                ->after('auto_check_out_after_minutes');

            $table->unsignedSmallInteger('max_location_accuracy')
                ->default(100)
                ->after('approval_mode');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_policies', function (Blueprint $table) {
            $table->dropColumn([
                'approval_mode',
                'max_location_accuracy',
            ]);
        });
    }
};
