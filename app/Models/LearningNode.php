<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningNode extends Model
{
    protected $fillable = ['slug', 'type', 'title', 'description', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class)->withPivot('is_primary');
    }

    public function pathNodes(): HasMany
    {
        return $this->hasMany(LearningPathNode::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function masteryStates(): HasMany
    {
        return $this->hasMany(MasteryState::class);
    }
}
