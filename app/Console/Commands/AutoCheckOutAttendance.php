<?php

namespace App\Console\Commands;

use App\Services\HR\AttendanceAutoCheckOutService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

class AutoCheckOutAttendance extends Command
{
    protected $signature = 'attendance:auto-check-out
        {--tenant= : Process one tenant ID only}
        {--at= : Evaluation time for testing}
        {--dry-run : Show due records without updating them}';

    protected $description =
        'Automatically close attendance records when employees forget to check out';

    public function handle(
        AttendanceAutoCheckOutService $service
    ): int {
        try {
            $now = $this->option('at')
                ? Carbon::parse(
                    (string) $this->option('at'),
                    config('app.timezone')
                )->utc()
                : now()->utc();

            $tenantOption = $this->option('tenant');
            $tenantId = null;

            if ($tenantOption !== null) {
                $tenantId = filter_var(
                    $tenantOption,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );

                if ($tenantId === false) {
                    $this->error('The tenant option must be a positive ID.');

                    return self::FAILURE;
                }
            }

            $summary = $service->processDueRecords(
                $now,
                $tenantId,
                (bool) $this->option('dry-run')
            );

            $this->table(
                ['Scanned', 'Due', 'Processed', 'Skipped', 'Failed'],
                [[
                    $summary['scanned'],
                    $summary['due'],
                    $summary['processed'],
                    $summary['skipped'],
                    $summary['failed'],
                ]]
            );

            if ($this->option('dry-run')) {
                $this->warn('Dry run: no attendance records were changed.');
            } else {
                $this->info('Automatic check-out processing completed.');
            }

            return $summary['failed'] > 0
                ? self::FAILURE
                : self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
