<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Task extends Model
{
    protected $fillable = ['slug', 'type', 'difficulty', 'estimated_minutes', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function learningNodes(): BelongsToMany
    {
        return $this->belongsToMany(LearningNode::class)->withPivot('is_primary');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(TaskVersion::class);
    }

    public function activeVersion(): HasOne
    {
        return $this->hasOne(TaskVersion::class)->where('active', true)->latestOfMany();
    }
}
