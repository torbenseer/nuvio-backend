<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NodeRelation extends Model
{
    protected $fillable = ['source_node_id', 'target_node_id', 'type'];

    public function sourceNode(): BelongsTo
    {
        return $this->belongsTo(LearningNode::class, 'source_node_id');
    }

    public function targetNode(): BelongsTo
    {
        return $this->belongsTo(LearningNode::class, 'target_node_id');
    }
}
