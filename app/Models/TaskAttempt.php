<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'task_id',
        'task_version_id',
        'review_id',
        'status',
        'result',
        'answer',
        'feedback_key',
        'feedback_text',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'answer' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function taskVersion(): BelongsTo
    {
        return $this->belongsTo(TaskVersion::class);
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
