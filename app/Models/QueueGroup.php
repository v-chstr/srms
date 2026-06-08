<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\QueueGroupMember;

class QueueGroup extends Model
{
    protected $fillable = ['queue_id', 'position'];

    public function queue(): BelongsTo  { return $this->belongsTo(Queue::class); }
    public function members(): HasMany  { return $this->hasMany(QueueGroupMember::class); }
}
