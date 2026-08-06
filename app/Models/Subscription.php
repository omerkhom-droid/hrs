<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'uuid',
        'tenant_id',
        'plan_id',
        'status',
        'billing_cycle',
        'price',
        'currency_code',
        'starts_at',
        'trial_ends_at',
        'ends_at',
        'auto_renew',
        'cancelled_at',
        'cancellation_reason',
        'plan_snapshot',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'auto_renew' => 'boolean',
            'plan_snapshot' => 'array',
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class)
            ->withTrashed();
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class)
            ->withTrashed();
    }
}