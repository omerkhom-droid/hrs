<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->enum('document_type', [
                'identity',
                'passport',
                'residency',
                'contract',
                'qualification',
                'certificate',
                'medical',
                'insurance',
                'bank',
                'license',
                'other',
            ])->default('other');

            $table->string('document_number', 100)->nullable();
            $table->string('title');
            $table->string('issuing_authority')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();

            /*
             * الملفات تحفظ في storage/app/private ولا تعرض بروابط عامة.
             */
            $table->string('disk', 50)->default('local');
            $table->string('file_path', 1000);
            $table->string('original_name');
            $table->string('mime_type', 150)->nullable();
            $table->string('file_extension', 20)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);

            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'tenant_id',
                'document_number',
            ]);

            $table->index([
                'tenant_id',
                'employee_id',
                'document_type',
            ]);

            $table->index([
                'tenant_id',
                'expiry_date',
                'is_verified',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
