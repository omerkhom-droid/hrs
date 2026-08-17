<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EmployeeMobileDevice extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'employee_id',
        'device_uuid',
        'platform',
        'device_name',
        'device_model',
        'os_version',
        'app_version',
        'push_token',
        'is_trusted',
        'is_active',
        'last_seen_at',
        'last_ip',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_trusted' => 'boolean',
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EmployeeMobileDevice $device) {
            if (!$device->uuid) {
                $device->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            $this->qualifyColumn('is_active'),
            true
        );
    }
}
