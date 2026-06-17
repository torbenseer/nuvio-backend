<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningPathNode extends Model
{
    protected $fillable = ['learning_path_id', 'learning_node_id', 'position'];

    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class);
    }

    public function learningNode(): BelongsTo
    {
        return $this->belongsTo(LearningNode::class);
    }
}
