<?php

namespace App\Services\HR;

use App\Models\PayrollPaymentBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PayrollBankFileService
{
    public function generate(
        PayrollPaymentBatch $batch,
        User $user
    ): PayrollPaymentBatch {
        $tenantId = (int) $user->tenant_id;

        if ((int) $batch->tenant_id !== $tenantId) {
            abort(404);
        }

        return DB::transaction(function () use (
            $batch,
            $user,
            $tenantId
        ) {
            $batch = PayrollPaymentBatch::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            $batch->recalculateTotals();
            $batch->refresh();

            if (!$batch->can_generate) {
                throw ValidationException::withMessages([
                    'batch' =>
                        'لا يمكن إنشاء الملف قبل فحص الدفعة ومعالجة جميع الاستثناءات.',
                ]);
            }

            $items = $batch->items()
                ->where('status', 'ready')
                ->orderBy('employee_number')
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'batch' =>
                        'لا تحتوي الدفعة على موظفين جاهزين للتحويل.',
                ]);
            }

            $contents = $this->buildCsv(
                $batch,
                $items
            );

            $fileName =
                $batch->batch_number . '.csv';

            $filePath =
                'tenants/'
                . $tenantId
                . '/payroll/payment-batches/'
                . $batch->uuid
                . '/'
                . $fileName;

            $disk = 'local';

            if (
                !Storage::disk($disk)->put(
                    $filePath,
                    $contents
                )
            ) {
                throw new RuntimeException(
                    'تعذر حفظ ملف التحويل البنكي.'
                );
            }

            $metadata = $batch->metadata ?? [];

            $metadata['generated_file'] = [
                'format' => 'generic_bank_csv',
                'generated_at' => now()->toIso8601String(),
                'generated_by' => $user->id,
                'items_count' => $items->count(),
            ];

            $batch->forceFill([
                'status' => 'generated',

                'file_disk' => $disk,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_mime_type' =>
                    'text/csv; charset=UTF-8',
                'file_size' =>
                    strlen($contents),
                'file_checksum' =>
                    hash('sha256', $contents),

                'generated_at' => now(),
                'generated_by' => $user->id,
                'updated_by' => $user->id,
                'metadata' => $metadata,
            ])->save();

            return $batch->refresh();
        });
    }


    private function buildCsv(
        PayrollPaymentBatch $batch,
        iterable $items
    ): string {
        $stream = fopen(
            'php://temp',
            'w+b'
        );

        if ($stream === false) {
            throw new RuntimeException(
                'تعذر إنشاء ملف التحويل.'
            );
        }

        /*
         * BOM حتى تظهر العربية بصورة صحيحة في Excel.
         */
        fwrite($stream, "\xEF\xBB\xBF");

        fputcsv($stream, [
            'Payment Reference',
            'Payment Date',
            'Employee Number',
            'Employee Name',
            'Bank Name',
            'Bank Code',
            'Branch Code',
            'Account Holder',
            'Account Number',
            'IBAN',
            'SWIFT',
            'Amount',
            'Currency',
        ]);

        foreach ($items as $item) {
            fputcsv($stream, [
                $batch->payment_reference,
                $batch->payment_date->format('Y-m-d'),
                $item->employee_number,
                $item->employee_name,
                $item->bank_name,
                $item->bank_code,
                $item->bank_branch_code,
                $item->account_holder_name,
                $item->account_number,
                $item->iban,
                $item->swift_code,
                number_format(
                    (float) $item->amount,
                    2,
                    '.',
                    ''
                ),
                $item->currency_code,
            ]);
        }

        rewind($stream);

        $contents = stream_get_contents($stream);

        fclose($stream);

        if ($contents === false) {
            throw new RuntimeException(
                'تعذر قراءة ملف التحويل.'
            );
        }

        return $contents;
    }
}