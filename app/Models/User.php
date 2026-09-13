<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_system_admin',
        'tenant_id',
        'is_active',
        'locale',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_system_admin' => 'boolean',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }



    /*
    |--------------------------------------------------------------------------
    | Leave Management Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * طلبات الإجازة التي قدمها المستخدم.
     */
    public function submittedLeaveRequests(): HasMany
    {
        return $this->hasMany(
            LeaveRequest::class,
            'requested_by'
        )->latest('requested_at');
    }


    /**
     * طلبات الإجازة التي اتخذ المستخدم قرارًا بشأنها.
     */
    public function decidedLeaveRequests(): HasMany
    {
        return $this->hasMany(
            LeaveRequest::class,
            'approved_by'
        )->latest('updated_at');
    }


    /**
     * طلبات الإجازة التي ألغاها المستخدم.
     */
    public function cancelledLeaveRequests(): HasMany
    {
        return $this->hasMany(
            LeaveRequest::class,
            'cancelled_by'
        )->latest('cancelled_at');
    }


    /**
     * طلبات الإجازة التي أنشأها المستخدم من لوحة الإدارة.
     */
    public function createdLeaveRequests(): HasMany
    {
        return $this->hasMany(
            LeaveRequest::class,
            'created_by'
        )->latest('created_at');
    }


    /*
    |--------------------------------------------------------------------------
    | Leave Approval Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * قائمة طلبات الإجازة المنتظرة للاعتماد داخل شركة المستخدم.
     */
    public function leaveApprovalQueue(): Builder
    {
        $query = LeaveRequest::query();

        if (!$this->tenant_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('tenant_id', $this->tenant_id)
            ->pending()
            ->oldest('requested_at');
    }


    /**
     * هل يستطيع المستخدم إدارة الإجازات؟
     */
    public function canManageLeaves(): bool
    {
        return $this->can('leave.manage');
    }


    /**
     * هل يستطيع المستخدم اعتماد أو رفض الإجازات؟
     */
    public function canApproveLeaves(): bool
    {
        return $this->can('leave.approve');
    }


    /**
     * هل يستطيع المستخدم استخدام الخدمة الذاتية للإجازات؟
     */
    public function canUseLeaveSelfService(): bool
    {
        return $this->can('self_service.leave');
    }
    
}