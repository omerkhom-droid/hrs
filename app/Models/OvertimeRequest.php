<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OvertimeRequest extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'attendance_record_id',
        'overtime_date',
        'timezone',
        'planned_start_at',
        'planned_end_at',
        'requested_minutes',
        'actual_minutes',
        'approved_minutes',
        'type',
        'status',
        'reason',
        'decision_notes',
        'requested_at',
        'approved_at',
        'rejected_at',
        'cancelled_at',
        'completed_at',
        'requested_by',
        'approved_by',
        'created_by',
        'metadata',
    ];

    protected $appends = [
        'status_label',
        'type_label',
        'requested_duration_label',
        'actual_duration_label',
        'approved_duration_label',
    ];

    protected function casts(): array
    {
        return [
            'overtime_date' => 'date',
            'planned_start_at' => 'datetime',
            'planned_end_at' => 'datetime',
            'requested_minutes' => 'integer',
            'actual_minutes' => 'integer',
            'approved_minutes' => 'integer',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (OvertimeRequest $request) {
            if (!$request->uuid) {
                $request->uuid = (string) Str::uuid();
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search) {
            $query->where('reason', 'like', "%{$search}%")
                ->orWhereHas(
                    'employee',
                    fn (Builder $query) => $query->search($search)
                );
        });
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'معتمد',
            'rejected' => 'مرفوض',
            'cancelled' => 'ملغي',
            'completed' => 'مكتمل',
            default => 'بانتظار الاعتماد',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'rest_day' => 'يوم راحة',
            'holiday' => 'عطلة رسمية',
            'emergency' => 'عمل طارئ',
            default => 'بعد الدوام',
        };
    }

    public function getRequestedDurationLabelAttribute(): string
    {
        return $this->durationLabel($this->requested_minutes);
    }

    public function getActualDurationLabelAttribute(): string
    {
        return $this->durationLabel($this->actual_minutes);
    }

    public function getApprovedDurationLabelAttribute(): string
    {
        return $this->durationLabel($this->approved_minutes ?? 0);
    }

    private function durationLabel(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
