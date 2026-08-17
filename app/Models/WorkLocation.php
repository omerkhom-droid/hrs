<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkLocation extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;


    protected $fillable = [
        'tenant_id',
        'branch_id',
        'code',
        'name',
        'name_en',
        'type',
        'country_code',
        'city',
        'address',
        'latitude',
        'longitude',
        'attendance_radius',
        'timezone',
        'is_active',
        'metadata',
    ];


    protected function casts(): array
    {
        return [
            'latitude' =>
                'decimal:7',

            'longitude' =>
                'decimal:7',

            'attendance_radius' =>
                'integer',

            'is_active' =>
                'boolean',

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


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function branch(): BelongsTo
    {
        return $this->belongsTo(
            Branch::class
        );
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
            $this->qualifyColumn('is_active'),
            true
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


    public function scopeOfType(
        Builder $query,
        ?string $type
    ): Builder {
        if (!$type) {
            return $query;
        }

        return $query->where(
            $this->qualifyColumn('type'),
            $type
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function getDisplayNameAttribute(): string
    {
        if (
            app()->getLocale() === 'en' &&
            $this->name_en
        ) {
            return $this->name_en;
        }

        return $this->name;
    }


    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'office' =>
                'مكتب',

            'site' =>
                'موقع عمل',

            'warehouse' =>
                'مستودع',

            'remote' =>
                'عمل عن بُعد',

            'other' =>
                'أخرى',

            default =>
                'غير محدد',
        };
    }


    /*
     * هل الموقع يحتوي على إحداثيات صالحة؟
     */
    public function hasCoordinates(): bool
    {
        return
            $this->latitude !== null &&
            $this->longitude !== null;
    }
}