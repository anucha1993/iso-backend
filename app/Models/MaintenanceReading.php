<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceReading extends Model
{
    protected $fillable = [
        'maintenance_record_id',
        'month',
        'check_date',
        'cpu_load',
        'memory_used',
        'disk_used',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'check_date' => 'date',
            'cpu_load' => 'float',
            'memory_used' => 'float',
            'disk_used' => 'float',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRecord::class, 'maintenance_record_id');
    }
}
