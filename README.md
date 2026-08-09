# رؤية يوم — HR SaaS

نظام موارد بشرية متعدد العملاء مبني باستخدام Laravel 12.

## المتطلبات

- PHP 8.2 أو أحدث
- Laravel 12
- MySQL
- Composer
- Node.js وNPM

## الوحدات الحالية

- إدارة منصة SaaS
- العملاء والباقات
- خصائص الباقات
- دورة حياة الاشتراكات
- بوابة الشركات
- المستخدمون والأدوار والصلاحيات
- عزل البيانات باستخدام Tenant ID
- معالجة حالات الاشتراكات المجدولة

## التشغيل المحلي

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve


أوامر الإدارة
php artisan saas:create-platform-admin
php artisan saas:create-tenant-user
php artisan saas:assign-tenant-owner
php artisan saas:subscriptions:process




### 4. افحص المشروع

```bash
php artisan optimize:clear
php artisan route:list
php artisan test
git diff --check
git grep -n "<<<<<<<"
git grep -n ">>>>>>>"