<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Access\TenantAccessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class CreateTenantUser extends Command
{
    protected $signature = 'saas:create-tenant-user';

    protected $description = 'Create the first owner user for a tenant';

    public function handle(): int
    {
        $code = strtoupper(
            trim($this->ask('كود العميل'))
        );

        $tenant = Tenant::query()
            ->where('code', $code)
            ->first();

        if (!$tenant) {
            $this->error('لم يتم العثور على العميل.');

            return self::FAILURE;
        }

        $this->info('العميل: ' . $tenant->name);

        $name = trim(
            $this->ask('اسم المستخدم')
        );

        if (!$name) {
            $this->error('اسم المستخدم مطلوب.');

            return self::FAILURE;
        }

        $email = strtolower(
            trim($this->ask('البريد الإلكتروني'))
        );

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('البريد الإلكتروني غير صحيح.');

            return self::FAILURE;
        }

        $exists = User::withTrashed()
            ->where('email', $email)
            ->exists();

        if ($exists) {
            $this->error('البريد الإلكتروني مستخدم مسبقًا.');

            return self::FAILURE;
        }

        $password = $this->secret(
            'كلمة المرور - 10 أحرف على الأقل'
        );

        if (!$password || strlen($password) < 10) {
            $this->error(
                'كلمة المرور يجب ألا تقل عن 10 أحرف.'
            );

            return self::FAILURE;
        }

        $confirmation = $this->secret(
            'تأكيد كلمة المرور'
        );

        if ($password !== $confirmation) {
            $this->error(
                'كلمتا المرور غير متطابقتين.'
            );

            return self::FAILURE;
        }

        try {

            $user = DB::transaction(
                function () use (
                    $tenant,
                    $name,
                    $email,
                    $password
                ) {

                    $user = User::create([
                        'tenant_id' => $tenant->id,
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make($password),
                        'is_system_admin' => false,
                        'is_active' => true,
                        'locale' => $tenant->locale ?? 'ar',
                    ]);

                    app(TenantAccessService::class)
                        ->assignRole(
                            $user,
                            $tenant,
                            'tenant_owner'
                        );

                    return $user;
                }
            );

        } catch (Throwable $e) {

            report($e);

            $this->error(
                'حدث خطأ أثناء إنشاء المستخدم أو إسناد الصلاحيات.'
            );

            $this->line($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();

        $this->info(
            'تم إنشاء مدير الشركة بنجاح.'
        );

        $this->line('ID: ' . $user->id);
        $this->line('العميل: ' . $tenant->name);
        $this->line('البريد: ' . $user->email);
        $this->line('الدور: tenant_owner');

        return self::SUCCESS;
    }
}