<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MaintenanceRound extends Model
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';

    protected $fillable = [
        'maintenance_record_id',
        'month',
        'status',
        'prepared_by',
        'prepared_name',
        'prepared_position',
        'prepared_signature_path',
        'prepared_signed_at',
        'approved_by',
        'approved_name',
        'approved_position',
        'approved_signature_path',
        'approved_signed_at',
        'rejected_reason',
        'rejected_at',
    ];

    protected $appends = ['prepared_signature_url', 'approved_signature_url'];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'prepared_signed_at' => 'datetime',
            'approved_signed_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRecord::class, 'maintenance_record_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getPreparedSignatureUrlAttribute(): ?string
    {
        return $this->signatureUrl($this->prepared_signature_path);
    }

    public function getApprovedSignatureUrlAttribute(): ?string
    {
        return $this->signatureUrl($this->approved_signature_path);
    }

    private function signatureUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
