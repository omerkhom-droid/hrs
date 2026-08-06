<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'monthly_price',
        'yearly_price',
        'currency_code',
        'trial_days',
        'max_users',
        'max_employees',
        'max_branches',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'yearly_price' => 'decimal:2',
            'trial_days' => 'integer',
            'max_users' => 'integer',
            'max_employees' => 'integer',
            'max_branches' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }


    public function features(): BelongsToMany
    {
        return $this->belongsToMany(
            Feature::class,
            'plan_features'
        )->withPivot('value')
         ->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }


}