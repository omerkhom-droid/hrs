<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AttendancePolicy extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'timezone',
        'late_grace_minutes',
        'early_leave_grace_minutes',
        'early_check_in_minutes',
        'late_check_out_minutes',
        'overtime_after_minutes',
        'rounding_rule',
        'allow_web',
        'allow_mobile',
        'require_geofence',
        'allow_outside_geofence',
        'require_photo',
        'auto_check_out',
        'weekend_days',
        'is_default',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'late_grace_minutes' => 'integer',
            'early_leave_grace_minutes' => 'integer',
            'early_check_in_minutes' => 'integer',
            'late_check_out_minutes' => 'integer',
            'overtime_after_minutes' => 'integer',
            'allow_web' => 'boolean',
            'allow_mobile' => 'boolean',
            'require_geofence' => 'boolean',
            'allow_outside_geofence' => 'boolean',
            'require_photo' => 'boolean',
            'auto_check_out' => 'boolean',
            'weekend_days' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AttendancePolicy $policy) {
            if (!$policy->uuid) {
                $policy->uuid = (string) Str::uuid();
            }
        });
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(
            WorkShift::class
        )->orderBy('start_time');
    }
}
