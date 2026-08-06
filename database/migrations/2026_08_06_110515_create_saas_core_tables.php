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
        | Tenants - عملاء المنصة
        |--------------------------------------------------------------------------
        */
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('slug')->unique();

            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();

            $table->char('country_code', 2)->default('SA');
            $table->string('timezone')->default('Asia/Riyadh');
            $table->string('locale', 10)->default('ar');
            $table->char('currency_code', 3)->default('SAR');

            $table->string('status', 30)
                ->default('active')
                ->index();

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });


        /*
        |--------------------------------------------------------------------------
        | Plans - الباقات
        |--------------------------------------------------------------------------
        */
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();
            $table->string('name');

            $table->text('description')->nullable();

            $table->decimal('monthly_price', 14, 2)->default(0);
            $table->decimal('yearly_price', 14, 2)->default(0);

            $table->char('currency_code', 3)->default('SAR');

            $table->unsignedInteger('trial_days')->default(15);

            $table->unsignedInteger('max_users')->nullable();
            $table->unsignedInteger('max_employees')->nullable();
            $table->unsignedInteger('max_branches')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });


        /*
        |--------------------------------------------------------------------------
        | Features - خصائص النظام
        |--------------------------------------------------------------------------
        */
        Schema::create('features', function (Blueprint $table) {
            $table->id();

            $table->string('code', 100)->unique();
            $table->string('name');

            $table->string('module', 100)->index();

            $table->string('type', 30)->default('boolean');

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });


        /*
        |--------------------------------------------------------------------------
        | Plan Features
        |--------------------------------------------------------------------------
        */
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plan_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('feature_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('value')->nullable();

            $table->timestamps();

            $table->unique([
                'plan_id',
                'feature_id'
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | Subscriptions - الاشتراكات
        |--------------------------------------------------------------------------
        */
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            $table->foreignId('tenant_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('plan_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('status', 30)
                ->default('trial')
                ->index();

            $table->string('billing_cycle', 30)
                ->default('monthly');

            $table->decimal('price', 14, 2)->default(0);

            $table->char('currency_code', 3)->default('SAR');

            $table->timestamp('starts_at');

            $table->timestamp('trial_ends_at')
                ->nullable();

            $table->timestamp('ends_at')
                ->nullable();

            $table->boolean('auto_renew')
                ->default(false);

            $table->timestamp('cancelled_at')
                ->nullable();

            $table->text('cancellation_reason')
                ->nullable();

            /*
             * نسخة من بيانات الباقة وقت الاشتراك.
             * حتى لو تغير سعر الباقة مستقبلاً يبقى
             * الاشتراك القديم محتفظاً ببياناته.
             */
            $table->json('plan_snapshot')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index([
                'tenant_id',
                'status'
            ]);

            $table->index([
                'starts_at',
                'ends_at'
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | Tenant Users
        |--------------------------------------------------------------------------
        | System Admin يبقى tenant_id = NULL.
        */
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained('tenants')
                ->restrictOnDelete();

            $table->index([
                'tenant_id',
                'is_active'
            ]);
        });
    }


    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex([
                'tenant_id',
                'is_active'
            ]);

            $table->dropConstrainedForeignId('tenant_id');
        });

        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('features');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('tenants');
    }
};