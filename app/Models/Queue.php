<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\QueueGroup;

class Queue extends Model
{
    protected $fillable = ['title', 'course_id', 'created_by', 'status', 'current_position'];

    public function course(): BelongsTo    { return $this->belongsTo(Course::class); }
    public function creator(): BelongsTo   { return $this->belongsTo(User::class, 'created_by'); }
    public function groups(): HasMany      { return $this->hasMany(QueueGroup::class)->orderBy('position'); }

    public function currentGroup(): ?QueueGroup
    {
        if ($this->current_position === 0) {
            return null;
        }
        return $this->groups()->where('position', $this->current_position)->first();
    }

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isActive(): bool     { return $this->status === 'active'; }
    public function isCompleted(): bool  { return $this->status === 'completed'; }
}
