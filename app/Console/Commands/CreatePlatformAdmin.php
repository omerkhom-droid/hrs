<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreatePlatformAdmin extends Command
{
    protected $signature = 'saas:create-platform-admin';

    protected $description = 'إنشاء مدير عام لمنصة رؤية يوم';

    public function handle(): int
    {
        $name = trim((string) $this->ask('اسم مدير المنصة'));
        $email = strtolower(trim((string) $this->ask('البريد الإلكتروني')));

        $validator = Validator::make(
            compact('name', 'email'),
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('يوجد مستخدم مسجل بهذا البريد الإلكتروني.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('كلمة المرور، 10 أحرف على الأقل');
        $passwordConfirmation = (string) $this->secret('تأكيد كلمة المرور');

        if ($password !== $passwordConfirmation) {
            $this->error('كلمتا المرور غير متطابقتين.');

            return self::FAILURE;
        }

        $passwordValidator = Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', 'min:10']]
        );

        if ($passwordValidator->fails()) {
            foreach ($passwordValidator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'tenant_id' => null,
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make($password),
            'is_platform_admin' => true,
            'is_active' => true,
        ]);

        $this->info('تم إنشاء مدير منصة رؤية يوم بنجاح.');

        return self::SUCCESS;
    }
}