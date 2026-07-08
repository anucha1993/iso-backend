<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceRecord extends Model
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';

    protected $fillable = [
        'form_template_id',
        'created_revision',
        'server_id',
        'year',
        'responsible',
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
        'note',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'prepared_signed_at' => 'datetime',
            'approved_signed_at' => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(MaintenanceEntry::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(MaintenanceReading::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(MaintenanceRound::class)->orderBy('month');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RecordAttachment::class)->latest();
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }
}
