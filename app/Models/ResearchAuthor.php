<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResearchAuthor extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'research_paper_id',
        'first_name',
        'last_name',
        'is_submitter',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_submitter' => 'boolean',
            'sort_order'   => 'integer',
        ];
    }

    public function paper(): BelongsTo
    {
        return $this->belongsTo(ResearchPaper::class, 'research_paper_id');
    }

    /**
     * Full name: "First Last"
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
