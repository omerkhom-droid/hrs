<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();

            $table->decimal('monthly_price', 12, 2)->default(0);
            $table->decimal('yearly_price', 12, 2)->default(0);
            $table->unsignedSmallInteger('trial_days')->default(15);

            // NULL تعني استخدام غير محدود
            $table->unsignedInteger('users_limit')->nullable();
            $table->unsignedInteger('employees_limit')->nullable();
            $table->unsignedInteger('companies_limit')->nullable();
            $table->unsignedInteger('branches_limit')->nullable();
            $table->unsignedInteger('storage_limit_mb')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('type', 20)->default('boolean');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plan_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('feature_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->boolean('is_enabled')->default(true);
            $table->string('value')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'feature_id']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('tenant_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('plan_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('status', 30)->default('trialing')->index();
            $table->string('billing_cycle', 20)->default('monthly');
            $table->decimal('amount', 12, 2)->default(0);

            $table->timestamp('starts_at');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->boolean('auto_renew')->default(false);
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('features');
        Schema::dropIfExists('plans');
    }
};