<?php

namespace App\Services\HR;

use App\Models\AttendancePolicy;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Support\Facades\DB;
use LogicException;

class AttendanceApprovalService
{
    public function applyAutomaticDecision(
        AttendanceRecord $record,
        AttendancePolicy $policy,
        ?WorkLocation $location
    ): void {
        $reasons = $this->reviewReasons(
            $record,
            $policy,
            $location
        );

        $automatic = $policy->approval_mode === 'auto_clean'
            && $reasons === [];

        $metadata = $record->metadata ?? [];
        $metadata['approval'] = [
            'mode' => $policy->approval_mode ?: 'manual',
            'source' => $automatic ? 'system' : 'pending_review',
            'evaluated_at' => now()->toIso8601String(),
            'review_reasons' => $reasons,
        ];

        $record->forceFill([
            'approval_status' => $automatic ? 'approved' : 'pending',
            'approved_at' => $automatic ? now() : null,
            'approved_by' => null,
            'metadata' => $metadata,
        ]);
    }

    public function reviewReasons(
        AttendanceRecord $record,
        AttendancePolicy $policy,
        ?WorkLocation $location
    ): array {
        $reasons = [];

        if (!$record->check_in_at || !$record->check_out_at) {
            $reasons[] = 'incomplete_punch';
        }

        if ($record->status !== 'present') {
            $reasons[] = 'exception_status';
        }

        if ($record->late_minutes > 0) {
            $reasons[] = 'late';
        }

        if ($record->early_leave_minutes > 0) {
            $reasons[] = 'early_leave';
        }

        if (
            $record->overtime_minutes > 0
            && $record->approved_overtime_minutes <= 0
        ) {
            $reasons[] = 'overtime_pending_approval';
        } elseif (
            $record->overtime_minutes > 0
            && $record->approved_overtime_minutes
                < $record->overtime_minutes
        ) {
            $reasons[] = 'overtime_partially_approved';
        }

        if (in_array($record->check_in_source, ['manual', 'system'], true)) {
            $reasons[] = 'manual_or_system_check_in';
        }

        if (in_array($record->check_out_source, ['manual', 'system'], true)) {
            $reasons[] = 'manual_or_system_check_out';
        }

        $metadata = $record->metadata ?? [];

        if (($policy->approval_mode ?: 'manual') === 'manual') {
            $reasons[] = 'manual_approval_mode';
        }

        if (!empty($metadata['auto_check_out'])) {
            $reasons[] = 'auto_check_out';
        }

        if ($policy->require_geofence) {
            $maximumAccuracy = max(
                1,
                (int) ($policy->max_location_accuracy ?? 100)
            );

            foreach (['check_in_accuracy', 'check_out_accuracy'] as $key) {
                if (!isset($metadata[$key])) {
                    $reasons[] = 'missing_location_accuracy';
                    break;
                }

                if (
                    (float) $metadata[$key] > $maximumAccuracy
                ) {
                    $reasons[] = 'low_location_accuracy';
                    break;
                }
            }

            if (!$location || !$location->hasCoordinates()) {
                $reasons[] = 'missing_work_location';
            } else {
                $radius = (int) $location->attendance_radius;

                if (
                    $record->check_in_distance === null
                    || $record->check_in_distance > $radius
                ) {
                    $reasons[] = 'check_in_outside_geofence';
                }

                if (
                    $record->check_out_distance === null
                    || $record->check_out_distance > $radius
                ) {
                    $reasons[] = 'check_out_outside_geofence';
                }
            }
        }

        if (
            $policy->require_photo
            && (!$record->check_in_photo_path || !$record->check_out_photo_path)
        ) {
            $reasons[] = 'missing_photo';
        }

        return array_values(array_unique($reasons));
    }

    public function bulkApprove(
        User $actor,
        array $recordIds
    ): array {
        $tenantId = (int) $actor->tenant_id;

        if (!$tenantId) {
            throw new LogicException('تعذر تحديد الشركة الحالية.');
        }

        $ids = collect($recordIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        return DB::transaction(function () use ($actor, $tenantId, $ids) {
            $records = AttendanceRecord::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get();

            $approved = 0;
            $skipped = 0;

            foreach ($records as $record) {
                if (
                    $record->approval_status === 'approved'
                    || $record->status === 'incomplete'
                    || !$record->check_in_at
                    || !$record->check_out_at
                ) {
                    $skipped++;
                    continue;
                }

                $metadata = $record->metadata ?? [];
                $metadata['approval'] = [
                    ...($metadata['approval'] ?? []),
                    'source' => 'bulk_manual',
                    'approved_at' => now()->toIso8601String(),
                    'approved_by' => $actor->id,
                ];

                $record->forceFill([
                    'approval_status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => $actor->id,
                    'metadata' => $metadata,
                ])->save();

                $approved++;
            }

            $missing = max(0, $ids->count() - $records->count());

            return [
                'requested' => $ids->count(),
                'approved' => $approved,
                'skipped' => $skipped + $missing,
            ];
        });
    }

    public function reasonLabels(array $reasons): array
    {
        $labels = [
            'incomplete_punch' => 'البصمة غير مكتملة',
            'manual_approval_mode' => 'السياسة تتطلب اعتمادًا يدويًا',
            'exception_status' => 'حالة حضور استثنائية',
            'late' => 'يوجد تأخير',
            'early_leave' => 'يوجد خروج مبكر',
            'overtime_pending_approval' =>
                'العمل الإضافي غير معتمد',
            'overtime_partially_approved' =>
                'العمل الإضافي معتمد جزئيًا',
            'manual_or_system_check_in' => 'الحضور يدوي أو آلي',
            'manual_or_system_check_out' => 'الانصراف يدوي أو آلي',
            'auto_check_out' => 'انصراف تلقائي',
            'missing_location_accuracy' => 'دقة الموقع غير مسجلة',
            'low_location_accuracy' => 'دقة الموقع منخفضة',
            'missing_work_location' => 'موقع العمل غير مكتمل',
            'check_in_outside_geofence' => 'الحضور خارج النطاق',
            'check_out_outside_geofence' => 'الانصراف خارج النطاق',
            'missing_photo' => 'صورة الإثبات غير مكتملة',
        ];

        return collect($reasons)
            ->map(fn (string $reason) => $labels[$reason] ?? $reason)
            ->values()
            ->all();
    }
}
