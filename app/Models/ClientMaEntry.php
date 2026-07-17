<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientMaEntry extends Model
{
    protected $fillable = [
        'client_ma_record_id',
        'client_machine_id',
        'snap_name',
        'snap_owner',
        'snap_floor',
        'snap_department',
        'status',
        'tasks',
        'metrics',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'tasks' => 'array',
            'metrics' => 'array',
        ];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(ClientMachine::class, 'client_machine_id');
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(ClientMaRecord::class, 'client_ma_record_id');
    }
}
