<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EmployeeDocument extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'document_type',
        'document_number',
        'title',
        'issuing_authority',
        'issue_date',
        'expiry_date',
        'disk',
        'file_path',
        'original_name',
        'mime_type',
        'file_extension',
        'file_size',
        'is_verified',
        'verified_at',
        'verified_by',
        'uploaded_by',
        'notes',
        'metadata',
    ];

    protected $appends = [
        'document_type_label',
        'expiry_status',
        'expiry_status_label',
        'formatted_file_size',
        'is_previewable',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'file_size' => 'integer',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EmployeeDocument $document) {
            if (!$document->uuid) {
                $document->uuid = (string) Str::uuid();
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'verified_by'
        );
    }

    public function scopeSearch(
        Builder $query,
        ?string $search
    ): Builder {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search) {
            $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('document_number', 'like', "%{$search}%")
                ->orWhere('issuing_authority', 'like', "%{$search}%")
                ->orWhere('original_name', 'like', "%{$search}%")
                ->orWhereHas('employee', function (Builder $query) use ($search) {
                    $query->search($search);
                });
        });
    }

    public function scopeExpiringWithin(
        Builder $query,
        int $days
    ): Builder {
        return $query
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [
                today(),
                today()->addDays($days),
            ]);
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return match ($this->document_type) {
            'identity' => 'هوية وطنية',
            'passport' => 'جواز سفر',
            'residency' => 'إقامة',
            'contract' => 'عقد',
            'qualification' => 'مؤهل علمي',
            'certificate' => 'شهادة',
            'medical' => 'مستند طبي',
            'insurance' => 'تأمين',
            'bank' => 'مستند بنكي',
            'license' => 'رخصة',
            'other' => 'أخرى',
            default => 'غير محدد',
        };
    }

    public function getExpiryStatusAttribute(): string
    {
        if (!$this->expiry_date) {
            return 'no_expiry';
        }

        if ($this->expiry_date->isPast()) {
            return 'expired';
        }

        if ($this->expiry_date->lte(today()->addDays(30))) {
            return 'expiring';
        }

        return 'valid';
    }

    public function getExpiryStatusLabelAttribute(): string
    {
        return match ($this->expiry_status) {
            'expired' => 'منتهي',
            'expiring' => 'قارب على الانتهاء',
            'valid' => 'ساري',
            default => 'بدون انتهاء',
        };
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = max(0, (int) $this->file_size);

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return number_format($bytes / (1024 * 1024), 1) . ' MB';
    }

    public function getIsPreviewableAttribute(): bool
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
        ], true);
    }
}
