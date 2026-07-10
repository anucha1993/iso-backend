<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientMachine extends Model
{
    protected $fillable = [
        'name',
        'asset_tag',
        'owner',
        'department',
        'floor',
        'os',
        'note',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
