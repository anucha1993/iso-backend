<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StandardProfile extends Model
{
    protected $fillable = [
        'name',
        'description',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function standards(): HasMany
    {
        return $this->hasMany(ChecklistStandard::class)->orderBy('order');
    }

    public function formTemplates(): HasMany
    {
        return $this->hasMany(FormTemplate::class);
    }
}
