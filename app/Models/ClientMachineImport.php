<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientMachineImport extends Model
{
    protected $fillable = [
        'filename',
        'path',
        'uploaded_by',
        'uploaded_by_name',
        'created_count',
        'updated_count',
        'skipped_count',
        'changes',
    ];

    protected function casts(): array
    {
        return ['changes' => 'array'];
    }
}
