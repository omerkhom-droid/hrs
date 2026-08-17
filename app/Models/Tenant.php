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

    
}