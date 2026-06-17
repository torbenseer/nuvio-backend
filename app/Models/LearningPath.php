<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningPath extends Model
{
    protected $fillable = ['subject_id', 'slug', 'title', 'type', 'estimated_minutes', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function pathNodes(): HasMany
    {
        return $this->hasMany(LearningPathNode::class)->orderBy('position');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
