<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RecordAttachment extends Model
{
    protected $fillable = [
        'maintenance_record_id',
        'month',
        'caption',
        'original_name',
        'path',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    protected $appends = ['url', 'is_pdf', 'is_image'];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'size' => 'integer',
        ];
    }

    public function getUrlAttribute(): ?string
    {
        if (! $this->path) {
            return null;
        }

        $disk = Storage::disk(config('filesystems.attachment', 'public'));

        // Presigned (temporary) URL for S3 / Cloudflare R2 private buckets;
        // fall back to a public URL for the local 'public' disk.
        try {
            return $disk->temporaryUrl($this->path, now()->addMinutes(60));
        } catch (\Throwable) {
            return $disk->url($this->path);
        }
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRecord::class, 'maintenance_record_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
