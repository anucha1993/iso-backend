<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientMaEntry extends Model
{
    protected $fillable = [
        'client_ma_record_id',
        'client_machine_id',
        'status',
        'tasks',
        'note',
    ];

    protected function casts(): array
    {
        return ['tasks' => 'array'];
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
