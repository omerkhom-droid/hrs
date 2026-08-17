<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Employee extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'employee_number',
        'attendance_code',
        'branch_id',
        'department_id',
        'job_title_id',
        'work_location_id',
        'manager_id',
        'first_name',
        'father_name',
        'grandfather_name',
        'family_name',
        'name_en',
        'identity_type',
        'identity_number',
        'identity_expiry_date',
        'nationality_code',
        'gender',
        'birth_date',
        'marital_status',
        'personal_email',
        'work_email',
        'personal_phone',
        'work_phone',
        'country_code',
        'city',
        'address',
        'emergency_contact_name',
        'emergency_contact_relation',
        'emergency_contact_phone',
        'employment_type',
        'employment_status',
        'hire_date',
        'probation_end_date',
        'confirmation_date',
        'termination_date',
        'termination_reason',
        'timezone',
        'photo_path',
        'notes',
        'metadata',
    ];

    protected $appends = [
        'full_name',
        'display_name',
        'employment_status_label',
        'employment_type_label',
    ];

    protected function casts(): array
    {
        return [
            'identity_expiry_date' =>
                'date',

            'birth_date' =>
                'date',

            'hire_date' =>
                'date',

            'probation_end_date' =>
                'date',

            'confirmation_date' =>
                'date',

            'termination_date' =>
                'date',

            'metadata' =>
                'array',

            'created_at' =>
                'datetime',

            'updated_at' =>
                'datetime',

            'deleted_at' =>
                'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Employee $employee) {
            if (!$employee->uuid) {
                $employee->uuid = (string) Str::uuid();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(
            Branch::class
        );
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class
        );
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(
            JobTitle::class
        );
    }

    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(
            WorkLocation::class
        );
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'manager_id'
        );
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(
            Employee::class,
            'manager_id'
        )->orderBy('first_name')
            ->orderBy('family_name');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(
            EmployeeDocument::class
        )->orderByDesc('created_at');
    }

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(
            EmployeeShiftAssignment::class
        )->orderByDesc('effective_from');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(
            AttendanceRecord::class
        )->orderByDesc('attendance_date');
    }

    
    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            $this->qualifyColumn('employment_status'),
            'active'
        );
    }

    public function scopeForBranch(
        Builder $query,
        ?int $branchId
    ): Builder {
        if (!$branchId) {
            return $query;
        }

        return $query->where(
            $this->qualifyColumn('branch_id'),
            $branchId
        );
    }

    public function scopeForDepartment(
        Builder $query,
        ?int $departmentId
    ): Builder {
        if (!$departmentId) {
            return $query;
        }

        return $query->where(
            $this->qualifyColumn('department_id'),
            $departmentId
        );
    }

    public function scopeWithEmploymentStatus(
        Builder $query,
        ?string $status
    ): Builder {
        if (!$status) {
            return $query;
        }

        return $query->where(
            $this->qualifyColumn('employment_status'),
            $status
        );
    }

    public function scopeSearch(
        Builder $query,
        ?string $search
    ): Builder {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search) {
            $query
                ->where('employee_number', 'like', "%{$search}%")
                ->orWhere('attendance_code', 'like', "%{$search}%")
                ->orWhere('first_name', 'like', "%{$search}%")
                ->orWhere('father_name', 'like', "%{$search}%")
                ->orWhere('grandfather_name', 'like', "%{$search}%")
                ->orWhere('family_name', 'like', "%{$search}%")
                ->orWhere('name_en', 'like', "%{$search}%")
                ->orWhere('identity_number', 'like', "%{$search}%")
                ->orWhere('personal_email', 'like', "%{$search}%")
                ->orWhere('work_email', 'like', "%{$search}%")
                ->orWhere('personal_phone', 'like', "%{$search}%")
                ->orWhere('work_phone', 'like', "%{$search}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function getFullNameAttribute(): string
    {
        return collect([
            $this->first_name,
            $this->father_name,
            $this->grandfather_name,
            $this->family_name,
        ])->filter()
            ->implode(' ');
    }

    public function getDisplayNameAttribute(): string
    {
        if (
            app()->getLocale() === 'en' &&
            $this->name_en
        ) {
            return $this->name_en;
        }

        return $this->full_name;
    }

    public function getEmploymentStatusLabelAttribute(): string
    {
        return match ($this->employment_status) {
            'draft' =>
                'مسودة',

            'probation' =>
                'فترة تجربة',

            'active' =>
                'على رأس العمل',

            'on_leave' =>
                'في إجازة',

            'suspended' =>
                'موقوف',

            'terminated' =>
                'منتهي الخدمة',

            default =>
                'غير محدد',
        };
    }

    public function getEmploymentTypeLabelAttribute(): string
    {
        return match ($this->employment_type) {
            'full_time' =>
                'دوام كامل',

            'part_time' =>
                'دوام جزئي',

            'contract' =>
                'عقد',

            'temporary' =>
                'مؤقت',

            'intern' =>
                'متدرب',

            'consultant' =>
                'مستشار',

            default =>
                'غير محدد',
        };
    }

    public function isActive(): bool
    {
        return $this->employment_status === 'active';
    }

    public function hasLoginAccount(): bool
    {
        return $this->user_id !== null;
    }
}