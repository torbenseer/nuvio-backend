<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskVersion extends Model
{
    protected $fillable = [
        'task_id',
        'version',
        'prompt',
        'input_schema',
        'answer_schema',
        'explanation',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'input_schema' => 'array',
            'answer_schema' => 'array',
            'active' => 'boolean',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
