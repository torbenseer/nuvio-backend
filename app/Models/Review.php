<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'learning_node_id',
        'task_id',
        'status',
        'due_at',
        'interval_days',
        'lapses',
        'last_attempted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'last_attempted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function learningNode(): BelongsTo
    {
        return $this->belongsTo(LearningNode::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TaskAttempt::class);
    }
}
