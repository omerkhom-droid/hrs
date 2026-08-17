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

class AttendanceRecord extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'work_shift_id',
        'work_location_id',
        'attendance_date',
        'timezone',
        'scheduled_check_in_at',
        'scheduled_check_out_at',
        'check_in_at',
        'check_out_at',
        'check_in_source',
        'check_out_source',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_distance',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_distance',
        'check_in_ip',
        'check_out_ip',
        'check_in_device',
        'check_out_device',
        'check_in_photo_path',
        'check_out_photo_path',
        'status',
        'work_minutes',
        'break_minutes',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'approval_status',
        'approved_at',
        'approved_by',
        'created_by',
        'notes',
        'metadata',
    ];

    protected $appends = [
        'status_label',
        'approval_status_label',
        'work_duration_label',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'scheduled_check_in_at' => 'datetime',
            'scheduled_check_out_at' => 'datetime',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'check_in_latitude' => 'decimal:7',
            'check_in_longitude' => 'decimal:7',
            'check_out_latitude' => 'decimal:7',
            'check_out_longitude' => 'decimal:7',
            'check_in_distance' => 'integer',
            'check_out_distance' => 'integer',
            'work_minutes' => 'integer',
            'break_minutes' => 'integer',
            'late_minutes' => 'integer',
            'early_leave_minutes' => 'integer',
            'overtime_minutes' => 'integer',
            'approved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AttendanceRecord $record) {
            if (!$record->uuid) {
                $record->uuid = (string) Str::uuid();
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(
            WorkShift::class,
            'work_shift_id'
        )->withTrashed();
    }

    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(AttendanceAdjustment::class);
    }

    public function scopeSearch(
        Builder $query,
        ?string $search
    ): Builder {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->whereHas(
            'employee',
            fn (Builder $query) => $query->search($search)
        );
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'present' => 'حاضر',
            'late' => 'متأخر',
            'absent' => 'غائب',
            'on_leave' => 'إجازة',
            'holiday' => 'عطلة',
            'remote' => 'عمل عن بعد',
            'incomplete' => 'غير مكتمل',
            default => 'غير محدد',
        };
    }

    public function getApprovalStatusLabelAttribute(): string
    {
        return match ($this->approval_status) {
            'approved' => 'معتمد',
            'rejected' => 'مرفوض',
            default => 'بانتظار الاعتماد',
        };
    }

    public function getWorkDurationLabelAttribute(): string
    {
        $hours = intdiv($this->work_minutes, 60);
        $minutes = $this->work_minutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
