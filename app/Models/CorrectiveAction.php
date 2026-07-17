<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CorrectiveAction extends Model
{
    protected $fillable = [
        'subject_type',
        'subject_id',
        'ref',
        'finding',
        'action',
        'status',
        'round_ref',
        'occurred_on',
        'created_by',
        'created_by_name',
    ];

    protected function casts(): array
    {
        return ['occurred_on' => 'date:Y-m-d'];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
