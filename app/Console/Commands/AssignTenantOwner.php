<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Access\TenantAccessService;
use Illuminate\Console\Command;
use Throwable;

class AssignTenantOwner extends Command
{
    protected $signature = 'saas:assign-tenant-owner';

    protected $description = 'Assign tenant_owner role to an existing tenant user';

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

        $this->info(
            'العميل: ' . $tenant->name
        );

        $email = strtolower(
            trim(
                $this->ask('البريد الإلكتروني للمستخدم')
            )
        );

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('البريد الإلكتروني غير صحيح.');

            return self::FAILURE;
        }

        $user = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->where('is_system_admin', false)
            ->first();

        if (!$user) {
            $this->error(
                'لم يتم العثور على المستخدم داخل هذا العميل.'
            );

            return self::FAILURE;
        }

        try {

            app(TenantAccessService::class)
                ->assignRole(
                    $user,
                    $tenant,
                    'tenant_owner'
                );

        } catch (Throwable $e) {

            report($e);

            $this->error(
                'تعذر إسناد دور مالك الحساب.'
            );

            $this->line($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();

        $this->info(
            'تم إسناد دور tenant_owner بنجاح.'
        );

        $this->line('المستخدم: ' . $user->name);
        $this->line('البريد: ' . $user->email);
        $this->line('العميل: ' . $tenant->name);

        return self::SUCCESS;
    }
}