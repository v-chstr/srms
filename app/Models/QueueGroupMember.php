<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueGroupMember extends Model
{
    protected $fillable = ['queue_group_id', 'user_id'];

    public function group(): BelongsTo { return $this->belongsTo(QueueGroup::class, 'queue_group_id'); }
    public function user(): BelongsTo  { return $this->belongsTo(User::class); }
}
