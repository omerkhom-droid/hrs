<?php

namespace App\Services\HR;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;


class LeaveRequestService
{
    public function __construct(
        private readonly LeaveBalanceService $balanceService,
        private readonly HolidayService $holidayService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Create And Update
    |--------------------------------------------------------------------------
    */

    public function createDraft(
        Employee $employee,
        LeaveType $leaveType,
        array $data,
        User $actor
    ): LeaveRequest {
        $this->ensureSameTenant(
            $employee,
            $leaveType,
            $actor
        );

        return DB::transaction(
            function () use (
                $employee,
                $leaveType,
                $data,
                $actor
            ) {
                $prepared = $this->prepareRequest(
                    employee: $employee,
                    leaveType: $leaveType,
                    data: $data
                );

                $request = LeaveRequest::create([
                    'tenant_id' =>
                        $employee->tenant_id,

                    'employee_id' =>
                        $employee->id,

                    'leave_type_id' =>
                        $leaveType->id,

                    'replacement_employee_id' =>
                        $data['replacement_employee_id']
                            ?? null,

                    'start_date' =>
                        $prepared['start_date'],

                    'end_date' =>
                        $prepared['end_date'],

                    'return_date' =>
                        $prepared['return_date'],

                    'start_session' =>
                        $prepared['start_session'],

                    'end_session' =>
                        $prepared['end_session'],

                    'requested_amount' =>
                        $prepared['requested_amount'],

                    'approved_amount' =>
                        0,

                    'status' =>
                        LeaveRequest::STATUS_DRAFT,

                    'reason' =>
                        $data['reason'] ?? null,

                    'handover_notes' =>
                        $data['handover_notes'] ?? null,

                    'contact_during_leave' =>
                        $data['contact_during_leave']
                            ?? null,

                    'attachment_path' =>
                        $data['attachment_path']
                            ?? null,

                    'current_approval_level' =>
                        0,

                    'required_approval_levels' =>
                        max(
                            1,
                            (int) (
                                $data[
                                    'required_approval_levels'
                                ] ?? 1
                            )
                        ),

                    'requested_by' =>
                        $actor->id,

                    'created_by' =>
                        $actor->id,

                    'metadata' =>
                        $data['metadata'] ?? null,
                ]);

                $request->days()->createMany(
                    $prepared['days']
                );

                return $request->load([
                    'employee',
                    'leaveType',
                    'replacementEmployee',
                    'days',
                ]);
            }
        );
    }

    public function updateDraft(
        LeaveRequest $request,
        LeaveType $leaveType,
        array $data,
        User $actor
    ): LeaveRequest {
        if (!$request->isDraft()) {
            throw new DomainException(
                'لا يمكن تعديل الطلب بعد إرساله للاعتماد.'
            );
        }

        $request->loadMissing('employee');

        $this->ensureSameTenant(
            $request->employee,
            $leaveType,
            $actor
        );

        return DB::transaction(
            function () use (
                $request,
                $leaveType,
                $data
            ) {
                $request = LeaveRequest::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $request->id
                    );

                $prepared = $this->prepareRequest(
                    employee: $request->employee,
                    leaveType: $leaveType,
                    data: $data,
                    excludedRequestId: $request->id
                );

                $request->update([
                    'leave_type_id' =>
                        $leaveType->id,

                    'replacement_employee_id' =>
                        $data['replacement_employee_id']
                            ?? null,

                    'start_date' =>
                        $prepared['start_date'],

                    'end_date' =>
                        $prepared['end_date'],

                    'return_date' =>
                        $prepared['return_date'],

                    'start_session' =>
                        $prepared['start_session'],

                    'end_session' =>
                        $prepared['end_session'],

                    'requested_amount' =>
                        $prepared['requested_amount'],

                    'reason' =>
                        $data['reason'] ?? null,

                    'handover_notes' =>
                        $data['handover_notes'] ?? null,

                    'contact_during_leave' =>
                        $data['contact_during_leave']
                            ?? null,

                    'attachment_path' =>
                        $data['attachment_path']
                            ?? $request->attachment_path,

                    'required_approval_levels' =>
                        max(
                            1,
                            (int) (
                                $data[
                                    'required_approval_levels'
                                ] ??
                                $request
                                    ->required_approval_levels
                            )
                        ),

                    'metadata' =>
                        $data['metadata'] ??
                        $request->metadata,
                ]);

                $request->days()->delete();

                $request->days()->createMany(
                    $prepared['days']
                );

                return $request->refresh()->load([
                    'employee',
                    'leaveType',
                    'replacementEmployee',
                    'days',
                ]);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow
    |--------------------------------------------------------------------------
    */

    public function submit(
        LeaveRequest $request,
        User $actor
    ): LeaveRequest {
        return DB::transaction(
            function () use (
                $request,
                $actor
            ) {
                $request = LeaveRequest::query()
                    ->with([
                        'employee',
                        'leaveType',
                        'days',
                    ])
                    ->lockForUpdate()
                    ->findOrFail(
                        $request->id
                    );

                if (!$request->canBeSubmitted()) {
                    throw new DomainException(
                        'هذا الطلب غير قابل للإرسال.'
                    );
                }

                $this->ensureActorTenant(
                    $actor,
                    (int) $request->tenant_id
                );

                $this->ensureAttachment(
                    $request
                );

                $this->reserveBalances(
                    $request
                );

                $request->forceFill([
                    'status' =>
                        LeaveRequest::STATUS_PENDING,

                    'requested_at' =>
                        now(),

                    'requested_by' =>
                        $actor->id,

                    'current_approval_level' =>
                        1,
                ])->save();

                $this->appendHistory(
                    request: $request,
                    action: 'submitted',
                    actor: $actor
                );

                return $request->refresh()->load([
                    'employee',
                    'leaveType',
                    'replacementEmployee',
                    'days',
                ]);
            }
        );
    }

    public function approve(
        LeaveRequest $request,
        User $approver,
        ?string $notes = null
    ): LeaveRequest {
        return DB::transaction(
            function () use (
                $request,
                $approver,
                $notes
            ) {
                $request = LeaveRequest::query()
                    ->with([
                        'employee',
                        'leaveType',
                        'days',
                    ])
                    ->lockForUpdate()
                    ->findOrFail(
                        $request->id
                    );

                if (!$request->canBeReviewed()) {
                    throw new DomainException(
                        'طلب الإجازة ليس بانتظار الاعتماد.'
                    );
                }

                $this->ensureActorTenant(
                    $approver,
                    (int) $request->tenant_id
                );

                if (
                    (int) $request
                        ->current_approval_level <
                    (int) $request
                        ->required_approval_levels
                ) {
                    $request->increment(
                        'current_approval_level'
                    );

                    $request->decision_notes =
                        $notes;

                    $request->save();

                    $this->appendHistory(
                        request: $request,
                        action: 'level_approved',
                        actor: $approver,
                        notes: $notes
                    );

                    return $request->refresh();
                }

                $this->consumeBalances(
                    $request
                );

                $request->forceFill([
                    'status' =>
                        LeaveRequest::STATUS_APPROVED,

                    'approved_amount' =>
                        $request->requested_amount,

                    'approved_at' =>
                        now(),

                    'approved_by' =>
                        $approver->id,

                    'decision_notes' =>
                        $notes,
                ])->save();

                $this->appendHistory(
                    request: $request,
                    action: 'approved',
                    actor: $approver,
                    notes: $notes
                );

                return $request->refresh()->load([
                    'employee',
                    'leaveType',
                    'replacementEmployee',
                    'days',
                    'approver',
                ]);
            }
        );
    }

    public function reject(
        LeaveRequest $request,
        User $approver,
        string $notes
    ): LeaveRequest {
        $notes = trim($notes);

        if ($notes === '') {
            throw new DomainException(
                'يجب إدخال سبب رفض الطلب.'
            );
        }

        return DB::transaction(
            function () use (
                $request,
                $approver,
                $notes
            ) {
                $request = LeaveRequest::query()
                    ->with([
                        'employee',
                        'leaveType',
                        'days',
                    ])
                    ->lockForUpdate()
                    ->findOrFail(
                        $request->id
                    );

                if (!$request->canBeReviewed()) {
                    throw new DomainException(
                        'طلب الإجازة ليس بانتظار الاعتماد.'
                    );
                }

                $this->ensureActorTenant(
                    $approver,
                    (int) $request->tenant_id
                );

                $this->releaseBalances(
                    $request
                );

                $request->forceFill([
                    'status' =>
                        LeaveRequest::STATUS_REJECTED,

                    'rejected_at' =>
                        now(),

                    'approved_by' =>
                        $approver->id,

                    'decision_notes' =>
                        $notes,
                ])->save();

                $this->appendHistory(
                    request: $request,
                    action: 'rejected',
                    actor: $approver,
                    notes: $notes
                );

                return $request->refresh();
            }
        );
    }

    public function cancel(
        LeaveRequest $request,
        User $actor,
        string $notes
    ): LeaveRequest {
        $notes = trim($notes);

        if ($notes === '') {
            throw new DomainException(
                'يجب إدخال سبب إلغاء الطلب.'
            );
        }

        return DB::transaction(
            function () use (
                $request,
                $actor,
                $notes
            ) {
                $request = LeaveRequest::query()
                    ->with([
                        'employee',
                        'leaveType',
                        'days',
                    ])
                    ->lockForUpdate()
                    ->findOrFail(
                        $request->id
                    );

                if (!$request->canBeCancelled()) {
                    throw new DomainException(
                        'لا يمكن إلغاء طلب الإجازة.'
                    );
                }

                $this->ensureActorTenant(
                    $actor,
                    (int) $request->tenant_id
                );

                if ($request->isPending()) {
                    $this->releaseBalances(
                        $request
                    );
                }

                if ($request->isApproved()) {
                    $this->reverseBalances(
                        $request
                    );
                }

                $request->forceFill([
                    'status' =>
                        LeaveRequest::STATUS_CANCELLED,

                    'cancelled_at' =>
                        now(),

                    'cancelled_by' =>
                        $actor->id,

                    'decision_notes' =>
                        $notes,
                ])->save();

                $this->appendHistory(
                    request: $request,
                    action: 'cancelled',
                    actor: $actor,
                    notes: $notes
                );

                return $request->refresh();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Request Calculation
    |--------------------------------------------------------------------------
    */

    private function prepareRequest(
        Employee $employee,
        LeaveType $leaveType,
        array $data,
        ?int $excludedRequestId = null
    ): array {
        if (!$leaveType->is_active) {
            throw new DomainException(
                'نوع الإجازة المحدد غير نشط.'
            );
        }

        if (
            !$leaveType->isAvailableForGender(
                $employee->gender ?? null
            )
        ) {
            throw new DomainException(
                'نوع الإجازة غير متاح لهذا الموظف.'
            );
        }

        $startDate = CarbonImmutable::parse(
            $data['start_date']
        )->startOfDay();

        $endDate = CarbonImmutable::parse(
            $data['end_date']
        )->startOfDay();

        if ($endDate->lt($startDate)) {
            throw new DomainException(
                'تاريخ النهاية يجب ألا يسبق تاريخ البداية.'
            );
        }

        if (
            empty($data['allow_backdated']) &&
            $startDate->lt(
                now()->startOfDay()
            )
        ) {
            throw new DomainException(
                'لا يمكن تقديم إجازة بتاريخ سابق.'
            );
        }

        $noticeDays = now()
            ->startOfDay()
            ->diffInDays(
                $startDate,
                false
            );

        if (
            $noticeDays <
            (int) $leaveType
                ->minimum_notice_days
        ) {
            throw new DomainException(
                'يجب تقديم الطلب قبل ' .
                $leaveType->minimum_notice_days .
                ' يوم على الأقل.'
            );
        }

        $this->ensureNoOverlap(
            employee: $employee,
            startDate: $startDate,
            endDate: $endDate,
            excludedRequestId: $excludedRequestId
        );

        $startSession =
            $data['start_session']
            ?? 'full_day';

        $endSession =
            $data['end_session']
            ?? 'full_day';

        $this->validateSessions(
            $leaveType,
            $startSession,
            $endSession
        );

        $employee->loadMissing(
            'tenant'
        );

        $tenant = $employee->tenant;

        if (!$tenant) {
            throw new DomainException(
                'تعذر تحديد الشركة التابعة للموظف.'
            );
        }

        $branchId = $employee->branch_id
            ? (int) $employee->branch_id
            : null;

        $excludedHolidayDates =
            $this->holidayService
                ->excludedLeaveDates(
                    tenant: $tenant,
                    startDate: $startDate,
                    endDate: $endDate,
                    branchId: $branchId
                );

        if ($leaveType->unit === 'hour') {

            if (!$startDate->isSameDay($endDate)) {
                throw new DomainException(
                    'الإجازة بالساعات يجب أن تكون في يوم واحد.'
                );
            }

            $dateKey =
                $startDate->toDateString();

            $isWorkingDay =
                $this->isWorkingDay(
                    $startDate
                ) &&
                !$excludedHolidayDates->has(
                    $dateKey
                );

            if (!$isWorkingDay) {
                throw new DomainException(
                    'لا يمكن طلب إجازة بالساعات خلال عطلة رسمية أو يوم راحة أسبوعية.'
                );
            }

            $requestedAmount = round(
                (float) (
                    $data['requested_amount']
                    ?? 0
                ),
                2
            );

            if ($requestedAmount <= 0) {
                throw new DomainException(
                    'عدد ساعات الإجازة غير صحيح.'
                );
            }

            $days = [[
                'tenant_id' =>
                    $employee->tenant_id,

                'leave_date' =>
                    $dateKey,

                'amount' =>
                    $requestedAmount,

                'is_working_day' =>
                    true,

                'is_paid' =>
                    $leaveType->payment_type !==
                    'unpaid',
            ]];

        } else {

            $days = $this->buildDayRows(
                tenantId:
                    (int) $employee->tenant_id,

                startDate:
                    $startDate,

                endDate:
                    $endDate,

                startSession:
                    $startSession,

                endSession:
                    $endSession,

                isPaid:
                    $leaveType->payment_type !==
                    'unpaid',

                excludedHolidayDates:
                    $excludedHolidayDates
            );

            $requestedAmount = round(
                collect($days)->sum('amount'),
                2
            );
        }

        if ($requestedAmount <= 0) {
            throw new DomainException(
                'الفترة المحددة لا تحتوي على أيام عمل.'
            );
        }

        if (
            $leaveType->maximum_consecutive_days &&
            $requestedAmount >
                (float) $leaveType
                    ->maximum_consecutive_days
        ) {
            throw new DomainException(
                'الحد الأقصى للإجازة المتصلة هو ' .
                $leaveType
                    ->maximum_consecutive_days .
                ' يوم.'
            );
        }

        return [
            'start_date' =>
                $startDate->toDateString(),

            'end_date' =>
                $endDate->toDateString(),
            
            'return_date' =>
                $this->resolveReturnDate(
                    employee: $employee,
                    endDate: $endDate
                ),
            
            'start_session' =>
                $startSession,

            'end_session' =>
                $endSession,

            'requested_amount' =>
                $requestedAmount,

            'days' =>
                $days,
        ];
    }

    private function buildDayRows(
        int $tenantId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        string $startSession,
        string $endSession,
        bool $isPaid,
        Collection $excludedHolidayDates
    ): array {
        $rows = [];
        $date = $startDate;

        while ($date->lte($endDate)) {
            $dateKey =
                $date->toDateString();

            /*
             * اليوم يكون يوم عمل فقط إذا:
             * - ليس عطلة أسبوعية.
             * - ليس عطلة رسمية مستبعدة من الإجازات.
             */
            $isWorkingDay =
                $this->isWorkingDay($date) &&
                !$excludedHolidayDates->has(
                    $dateKey
                );

            $amount =
                $isWorkingDay
                    ? 1.0
                    : 0.0;

            if (
                $isWorkingDay &&
                $date->isSameDay($startDate) &&
                $startSession !== 'full_day'
            ) {
                $amount = 0.5;
            }

            if (
                $isWorkingDay &&
                $date->isSameDay($endDate) &&
                $endSession !== 'full_day'
            ) {
                $amount = 0.5;
            }

            if (
                $startDate->isSameDay($endDate) &&
                $isWorkingDay &&
                $startSession === 'full_day' &&
                $endSession === 'full_day'
            ) {
                $amount = 1.0;
            }

            $rows[] = [
                'tenant_id' =>
                    $tenantId,

                'leave_date' =>
                    $dateKey,

                'amount' =>
                    $amount,

                'is_working_day' =>
                    $isWorkingDay,

                'is_paid' =>
                    $isPaid &&
                    $isWorkingDay,
            ];

            $date =
                $date->addDay();
        }

        return $rows;
    }

    private function resolveReturnDate(
        Employee $employee,
        CarbonImmutable $endDate
    ): string {
        $employee->loadMissing(
            'tenant'
        );

        if (!$employee->tenant) {
            throw new DomainException(
                'تعذر تحديد الشركة التابعة للموظف.'
            );
        }

        $returnDate =
            $endDate->addDay();

        $branchId =
            $employee->branch_id
                ? (int) $employee->branch_id
                : null;

        /*
         * حد حماية لمنع أي دورة غير منتهية
         * بسبب إعدادات غير صحيحة.
         */
        for (
            $attempt = 0;
            $attempt < 370;
            $attempt++
        ) {
            $isWeekend =
                !$this->isWorkingDay(
                    $returnDate
                );

            $isHoliday = false;

            if (!$isWeekend) {
                $isHoliday =
                    $this->holidayService
                        ->isExcludedFromLeave(
                            tenant:
                                $employee->tenant,

                            date:
                                $returnDate,

                            branchId:
                                $branchId
                        );
            }

            if (
                !$isWeekend &&
                !$isHoliday
            ) {
                return $returnDate
                    ->toDateString();
            }

            $returnDate =
                $returnDate->addDay();
        }

        throw new DomainException(
            'تعذر تحديد تاريخ العودة إلى العمل.'
        );
    }

    private function isWorkingDay(
        CarbonImmutable $date
    ): bool {
        $weekendDays = config(
            'hr.leave.weekend_days',
            [5, 6]
        );

        return !in_array(
            $date->dayOfWeekIso,
            $weekendDays,
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Balance Operations
    |--------------------------------------------------------------------------
    */

    private function reserveBalances(
        LeaveRequest $request
    ): void {
        if (
            !$request->leaveType
                ->requires_balance
        ) {
            return;
        }

        foreach (
            $this->amountsByYear($request)
            as $year => $amount
        ) {
            $balance =
                $this->balanceService
                    ->getOrCreate(
                        employee:
                            $request->employee,
                        leaveType:
                            $request->leaveType,
                        year:
                            (int) $year
                    );

            $this->balanceService->reserve(
                $balance,
                $amount
            );
        }
    }

    private function releaseBalances(
        LeaveRequest $request
    ): void {
        if (
            !$request->leaveType
                ->requires_balance
        ) {
            return;
        }

        foreach (
            $this->amountsByYear($request)
            as $year => $amount
        ) {
            $balance =
                $this->balanceService
                    ->getOrCreate(
                        employee:
                            $request->employee,
                        leaveType:
                            $request->leaveType,
                        year:
                            (int) $year
                    );

            $this->balanceService
                ->releaseReservation(
                    $balance,
                    $amount
                );
        }
    }

    private function consumeBalances(
        LeaveRequest $request
    ): void {
        if (
            !$request->leaveType
                ->requires_balance
        ) {
            return;
        }

        foreach (
            $this->amountsByYear($request)
            as $year => $amount
        ) {
            $balance =
                $this->balanceService
                    ->getOrCreate(
                        employee:
                            $request->employee,
                        leaveType:
                            $request->leaveType,
                        year:
                            (int) $year
                    );

            $this->balanceService
                ->consumeReservation(
                    balance: $balance,
                    amount: $amount,
                    leaveRequestId: $request->id
                );
        }
    }

    private function reverseBalances(
        LeaveRequest $request
    ): void {
        if (
            !$request->leaveType
                ->requires_balance
        ) {
            return;
        }

        foreach (
            $this->amountsByYear($request)
            as $year => $amount
        ) {
            $balance =
                $this->balanceService
                    ->getOrCreate(
                        employee:
                            $request->employee,
                        leaveType:
                            $request->leaveType,
                        year:
                            (int) $year
                    );

            $this->balanceService
                ->reverseUsage(
                    balance: $balance,
                    amount: $amount,
                    leaveRequestId: $request->id
                );
        }
    }

    private function amountsByYear(
        LeaveRequest $request
    ): array {
        return $request->days
            ->groupBy(
                fn ($day) =>
                    $day->leave_date->year
            )
            ->map(
                fn ($days) =>
                    round(
                        (float) $days->sum(
                            'amount'
                        ),
                        2
                    )
            )
            ->filter(
                fn ($amount) =>
                    $amount > 0
            )
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Helpers
    |--------------------------------------------------------------------------
    */

    private function ensureNoOverlap(
        Employee $employee,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?int $excludedRequestId = null
    ): void {
        $query = LeaveRequest::query()
            ->where(
                'employee_id',
                $employee->id
            )
            ->whereIn(
                'status',
                [
                    LeaveRequest::STATUS_PENDING,
                    LeaveRequest::STATUS_APPROVED,
                ]
            )
            ->overlapping(
                $startDate->toDateString(),
                $endDate->toDateString()
            );

        if ($excludedRequestId) {
            $query->whereKeyNot(
                $excludedRequestId
            );
        }

        if ($query->exists()) {
            throw new DomainException(
                'يوجد طلب إجازة آخر متداخل مع الفترة المحددة.'
            );
        }
    }

    private function validateSessions(
        LeaveType $leaveType,
        string $startSession,
        string $endSession
    ): void {
        $allowed = [
            'full_day',
            'first_half',
            'second_half',
        ];

        if (
            !in_array(
                $startSession,
                $allowed,
                true
            ) ||
            !in_array(
                $endSession,
                $allowed,
                true
            )
        ) {
            throw new DomainException(
                'فترة اليوم المحددة غير صحيحة.'
            );
        }

        if (
            (
                $startSession !== 'full_day' ||
                $endSession !== 'full_day'
            ) &&
            !$leaveType->allow_half_day
        ) {
            throw new DomainException(
                'نوع الإجازة لا يسمح بنصف يوم.'
            );
        }
    }

    private function ensureAttachment(
        LeaveRequest $request
    ): void {
        if (
            $request->leaveType
                ->requires_attachment &&
            !$request->attachment_path
        ) {
            throw new DomainException(
                'يجب إرفاق مستند مع طلب الإجازة.'
            );
        }
    }

    private function ensureSameTenant(
        Employee $employee,
        LeaveType $leaveType,
        User $actor
    ): void {
        if (
            (int) $employee->tenant_id !==
            (int) $leaveType->tenant_id
        ) {
            throw new DomainException(
                'الموظف ونوع الإجازة لا يتبعان الشركة نفسها.'
            );
        }

        $this->ensureActorTenant(
            $actor,
            (int) $employee->tenant_id
        );
    }

    private function ensureActorTenant(
        User $actor,
        int $tenantId
    ): void {
        if ($actor->is_system_admin) {
            return;
        }

        if (
            (int) $actor->tenant_id !==
            $tenantId
        ) {
            throw new DomainException(
                'المستخدم لا يتبع الشركة المحددة.'
            );
        }
    }

    private function appendHistory(
        LeaveRequest $request,
        string $action,
        User $actor,
        ?string $notes = null
    ): void {
        $metadata =
            $request->metadata ?? [];

        $history =
            $metadata['history'] ?? [];

        $history[] = [
            'action' =>
                $action,

            'user_id' =>
                $actor->id,

            'user_name' =>
                $actor->name,

            'notes' =>
                $notes,

            'recorded_at' =>
                now()->toIso8601String(),
        ];

        $metadata['history'] =
            $history;

        $request->metadata =
            $metadata;

        $request->save();
    }
}