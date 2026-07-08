<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceEntry extends Model
{
    public const STATUS_CHECKED = 'checked'; // '/'
    public const STATUS_FAULT   = 'fault';   // 'X'

    protected $fillable = [
        'maintenance_record_id',
        'checklist_item_id',
        'month',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRecord::class, 'maintenance_record_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class, 'checklist_item_id');
    }
}
