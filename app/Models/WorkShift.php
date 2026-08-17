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

class WorkShift extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'attendance_policy_id',
        'code',
        'name',
        'name_en',
        'shift_type',
        'start_time',
        'end_time',
        'crosses_midnight',
        'break_minutes',
        'working_minutes',
        'work_days',
        'is_default',
        'is_active',
        'metadata',
    ];

    protected $appends = [
        'shift_type_label',
        'time_range',
    ];

    protected function casts(): array
    {
        return [
            'crosses_midnight' => 'boolean',
            'break_minutes' => 'integer',
            'working_minutes' => 'integer',
            'work_days' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WorkShift $shift) {
            if (!$shift->uuid) {
                $shift->uuid = (string) Str::uuid();
            }
        });
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(
            AttendancePolicy::class,
            'attendance_policy_id'
        );
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(
            EmployeeShiftAssignment::class
        );
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(
            AttendanceRecord::class
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            $this->qualifyColumn('is_active'),
            true
        );
    }

    public function getShiftTypeLabelAttribute(): string
    {
        return match ($this->shift_type) {
            'regular' => 'ثابتة',
            'flexible' => 'مرنة',
            'night' => 'ليلية',
            default => 'غير محدد',
        };
    }

    public function getTimeRangeAttribute(): string
    {
        return substr((string) $this->start_time, 0, 5)
            . ' - '
            . substr((string) $this->end_time, 0, 5);
    }
}
