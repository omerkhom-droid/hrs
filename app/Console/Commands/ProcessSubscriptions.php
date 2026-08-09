<?php

namespace App\Console\Commands;

use App\Services\SaaS\SubscriptionStateService;
use Illuminate\Console\Command;

class ProcessSubscriptions extends Command
{
    protected $signature =
        'saas:subscriptions:process';

    protected $description =
        'Process subscription expirations and scheduled activations';

    public function handle(
        SubscriptionStateService $service
    ): int {

        $result = $service->process();

        $this->info('تمت معالجة الاشتراكات.');

        $this->table(
            [
                'العملية',
                'العدد',
            ],
            [
                [
                    'اشتراكات انتهت',
                    $result['expired'],
                ],
                [
                    'اشتراكات تم تفعيلها',
                    $result['activated'],
                ],
                [
                    'تم تجاوزها بسبب تعارض',
                    $result['skipped'],
                ],
            ]
        );

        return self::SUCCESS;
    }
}