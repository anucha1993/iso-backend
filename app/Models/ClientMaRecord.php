<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ClientMaRecord extends Model
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';

    protected $fillable = [
        'form_template_id',
        'year',
        'month',
        'responsible',
        'tasks',
        'report_files',
        'note',
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
    ];

    protected $appends = ['prepared_signature_url', 'approved_signature_url'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'tasks' => 'array',
            'report_files' => 'array',
            'prepared_signed_at' => 'datetime',
            'approved_signed_at' => 'datetime',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(ClientMaEntry::class);
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function getPreparedSignatureUrlAttribute(): ?string
    {
        return $this->prepared_signature_path ? Storage::disk('public')->url($this->prepared_signature_path) : null;
    }

    public function getApprovedSignatureUrlAttribute(): ?string
    {
        return $this->approved_signature_path ? Storage::disk('public')->url($this->approved_signature_path) : null;
    }
}
