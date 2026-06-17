<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function learningNodes(): BelongsToMany
    {
        return $this->belongsToMany(LearningNode::class);
    }

    public function learningPaths(): HasMany
    {
        return $this->hasMany(LearningPath::class);
    }
}
