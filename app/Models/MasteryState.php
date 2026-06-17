<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasteryState extends Model
{
    protected $fillable = [
        'user_id',
        'learning_node_id',
        'status',
        'last_practiced_at',
        'retained_at',
    ];

    protected function casts(): array
    {
        return [
            'last_practiced_at' => 'datetime',
            'retained_at' => 'datetime',
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
}
