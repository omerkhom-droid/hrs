<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;


    protected $fillable = [
        'tenant_id',
        'branch_id',
        'parent_id',
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(
            Branch::class
        );
    }


    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            Department::class,
            'parent_id'
        );
    }


    public function children(): HasMany
    {
        return $this->hasMany(
            Department::class,
            'parent_id'
        )->orderBy('sort_order')
            ->orderBy('name');
    }


    /*
     * تحميل الشجرة التنظيمية كاملة.
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()
            ->with('childrenRecursive');
    }


    public function jobTitles(): HasMany
    {
        return $this->hasMany(
            JobTitle::class
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


    public function scopeRoots(
        Builder $query
    ): Builder {
        return $query->whereNull(
            $this->qualifyColumn('parent_id')
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


    public function getIsRootAttribute(): bool
    {
        return $this->parent_id === null;
    }
}