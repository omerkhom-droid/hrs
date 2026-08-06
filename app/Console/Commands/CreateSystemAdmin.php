<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateSystemAdmin extends Command
{
    protected $signature = 'system:create-admin';

    protected $description = 'إنشاء مدير النظام الرئيسي';

    public function handle(): int
    {
        $name = trim(
            (string) $this->ask('اسم مدير النظام')
        );

        $email = Str::lower(
            trim(
                (string) $this->ask('البريد الإلكتروني')
            )
        );

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('البريد الإلكتروني غير صحيح.');

            return self::FAILURE;
        }

        if (User::withTrashed()->where('email', $email)->exists()) {
            $this->error('البريد الإلكتروني مستخدم مسبقًا.');

            return self::FAILURE;
        }

        $password = (string) $this->secret(
            'كلمة المرور - 10 أحرف على الأقل'
        );

        $confirmation = (string) $this->secret(
            'تأكيد كلمة المرور'
        );

        if (mb_strlen($password) < 10) {
            $this->error('كلمة المرور يجب ألا تقل عن 10 أحرف.');

            return self::FAILURE;
        }

        if ($password !== $confirmation) {
            $this->error('كلمتا المرور غير متطابقتين.');

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_system_admin' => true,
            'is_active' => true,
            'locale' => 'ar',
        ]);

        $this->info('تم إنشاء مدير النظام بنجاح.');

        return self::SUCCESS;
    }
}