<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobTitle extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;


    protected $fillable = [
        'tenant_id',
        'department_id',
        'code',
        'name',
        'name_en',
        'description',
        'sort_order',
        'is_active',
        'metadata',
    ];


    protected function casts(): array
    {
        return [
            'sort_order' =>
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class
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
}