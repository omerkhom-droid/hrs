<?php

namespace App\Notifications;

use App\Models\EmployeeLoan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EmployeeLoanStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly EmployeeLoan $loan,
        private readonly string $event
    ) {
    }

    public function via(
        object $notifiable
    ): array {
        return [
            'database',
        ];
    }

    public function toDatabase(
        object $notifiable
    ): array {
        return match ($this->event) {
            'submitted' => $this->payload(
                titleAr: 'تم إرسال طلب السلفة',
                titleEn: 'Loan Request Submitted',
                messageAr:
                    'تم إرسال طلب السلفة رقم ' .
                    $this->loan->request_number .
                    ' للاعتماد.',
                messageEn:
                    'Loan request ' .
                    $this->loan->request_number .
                    ' has been submitted for approval.',
            ),

            'approved' => $this->payload(
                titleAr: 'تم اعتماد طلب السلفة',
                titleEn: 'Loan Request Approved',
                messageAr:
                    'تم اعتماد طلب السلفة رقم ' .
                    $this->loan->request_number .
                    ' بمبلغ ' .
                    number_format(
                        (float) $this->loan->approved_amount,
                        2
                    ) .
                    ' ' .
                    ($this->loan->currency_code ?? 'SAR') .
                    '.',
                messageEn:
                    'Loan request ' .
                    $this->loan->request_number .
                    ' has been approved for ' .
                    number_format(
                        (float) $this->loan->approved_amount,
                        2
                    ) .
                    ' ' .
                    ($this->loan->currency_code ?? 'SAR') .
                    '.',
            ),

            'rejected' => $this->payload(
                titleAr: 'تم رفض طلب السلفة',
                titleEn: 'Loan Request Rejected',
                messageAr:
                    'تم رفض طلب السلفة رقم ' .
                    $this->loan->request_number .
                    (
                        $this->loan->rejection_reason
                            ? '، السبب: ' .
                                $this->loan->rejection_reason
                            : '.'
                    ),
                messageEn:
                    'Loan request ' .
                    $this->loan->request_number .
                    ' has been rejected' .
                    (
                        $this->loan->rejection_reason
                            ? '. Reason: ' .
                                $this->loan->rejection_reason
                            : '.'
                    ),
            ),

            'cancelled' => $this->payload(
                titleAr: 'تم إلغاء طلب السلفة',
                titleEn: 'Loan Request Cancelled',
                messageAr:
                    'تم إلغاء طلب السلفة رقم ' .
                    $this->loan->request_number .
                    '.',
                messageEn:
                    'Loan request ' .
                    $this->loan->request_number .
                    ' has been cancelled.',
            ),

            'completed' => $this->payload(
                titleAr: 'اكتمل سداد السلفة',
                titleEn: 'Loan Repayment Completed',
                messageAr:
                    'تم سداد جميع أقساط السلفة رقم ' .
                    $this->loan->request_number .
                    '.',
                messageEn:
                    'All installments for loan ' .
                    $this->loan->request_number .
                    ' have been paid.',
            ),

            default => $this->payload(
                titleAr: 'تحديث طلب السلفة',
                titleEn: 'Loan Request Updated',
                messageAr:
                    'تم تحديث طلب السلفة رقم ' .
                    $this->loan->request_number .
                    '.',
                messageEn:
                    'Loan request ' .
                    $this->loan->request_number .
                    ' has been updated.',
            ),
        };
    }

    private function payload(
        string $titleAr,
        string $titleEn,
        string $messageAr,
        string $messageEn
    ): array {
        return [
            'notification_type' =>
                'employee_loan',

            'event' =>
                $this->event,

            'title_ar' =>
                $titleAr,

            'title_en' =>
                $titleEn,

            'message_ar' =>
                $messageAr,

            'message_en' =>
                $messageEn,

            'entity_type' =>
                'employee_loan',

            'entity_uuid' =>
                $this->loan->uuid,

            'request_number' =>
                $this->loan->request_number,

            'status' =>
                $this->loan->status,

            'created_at' =>
                now()->toIso8601String(),
        ];
    }
}