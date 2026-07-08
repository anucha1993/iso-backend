<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function templates(): HasMany
    {
        return $this->hasMany(FormTemplate::class)->orderBy('order');
    }
}
