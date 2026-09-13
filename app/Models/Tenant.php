<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id',
        'uuid',
        'code',
        'name',
        'slug',
        'contact_name',
        'email',
        'phone',
        'country_code',
        'timezone',
        'locale',
        'currency_code',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Organization Structure
    |--------------------------------------------------------------------------
    */

    public function branches(): HasMany
    {
        return $this->hasMany(
            Branch::class
        );
    }


    public function mainBranch(): HasOne
    {
        return $this->hasOne(
            Branch::class
        )->where(
            'is_main',
            true
        );
    }


    public function departments(): HasMany
    {
        return $this->hasMany(
            Department::class
        );
    }


    public function jobTitles(): HasMany
    {
        return $this->hasMany(
            JobTitle::class
        );
    }


    public function workLocations(): HasMany
    {
        return $this->hasMany(
            WorkLocation::class
        );
    }

    public function payrollSetting(): HasOne
    {
        return $this->hasOne(
            PayrollSetting::class
        );
    }
    

    /*
    |--------------------------------------------------------------------------
    | Leave Management Relationships
    |--------------------------------------------------------------------------
    */

    public function leaveTypes(): HasMany
    {
        return $this->hasMany(
            LeaveType::class,
            'tenant_id'
        )->orderBy('sort_order')
            ->orderBy('name');
    }

    public function activeLeaveTypes(): HasMany
    {
        return $this->hasMany(
            LeaveType::class,
            'tenant_id'
        )->where(
            'is_active',
            true
        )->orderBy('sort_order')
            ->orderBy('name');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(
            LeaveRequest::class,
            'tenant_id'
        )->latest('id');
    }

    public function pendingLeaveRequests(): HasMany
    {
        return $this->hasMany(
            LeaveRequest::class,
            'tenant_id'
        )->where(
            'status',
            LeaveRequest::STATUS_PENDING
        )->oldest('requested_at');
    }

    public function approvedLeaveRequests(): HasMany
    {
        return $this->hasMany(
            LeaveRequest::class,
            'tenant_id'
        )->where(
            'status',
            LeaveRequest::STATUS_APPROVED
        )->latest('approved_at');
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(
            LeaveBalance::class,
            'tenant_id'
        )->orderByDesc('year');
    }

    public function leaveRequestDays(): HasMany
    {
        return $this->hasMany(
            LeaveRequestDay::class,
            'tenant_id'
        )->orderByDesc('leave_date');
    }

    public function leaveBalanceTransactions(): HasMany
    {
        return $this->hasMany(
            LeaveBalanceTransaction::class,
            'tenant_id'
        )->latest('effective_date')
            ->latest('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Leave Management Helpers
    |--------------------------------------------------------------------------
    */

    public function pendingLeaveRequestsCount(): int
    {
        return $this->pendingLeaveRequests()
            ->count();
    }

    public function employeesOnLeaveCount(
        ?string $date = null
    ): int {
        $date ??=
            now()->toDateString();

        return $this->employees()
            ->whereHas(
                'leaveRequests',
                function ($query) use ($date) {
                    $query
                        ->where(
                            'status',
                            LeaveRequest::STATUS_APPROVED
                        )
                        ->whereDate(
                            'start_date',
                            '<=',
                            $date
                        )
                        ->whereDate(
                            'end_date',
                            '>=',
                            $date
                        );
                }
            )
            ->count();
    }

    public function hasLeaveManagementSetup(): bool
    {
        return $this->leaveTypes()
            ->exists();
    }
}