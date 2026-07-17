<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormTemplate extends Model
{
    protected $fillable = [
        'form_category_id',
        'code',
        'name',
        'revision',
        'frequency_note',
        'tags',
        'module_key',
        'standard_profile_id',
        'route',
        'description',
        'confidentiality',
        'dar_log',
        'effective_date',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'tags' => 'array',
            'effective_date' => 'date:Y-m-d',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FormCategory::class, 'form_category_id');
    }

    public function standardProfile(): BelongsTo
    {
        return $this->belongsTo(StandardProfile::class, 'standard_profile_id');
    }
}
