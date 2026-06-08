<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Annotation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'research_paper_id',
        'adviser_id',
        'page',
        'type',
        'x',
        'y',
        'w',
        'h',
        'content',
        'color',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'page' => 'integer',
            'x' => 'float',
            'y' => 'float',
            'w' => 'float',
            'h' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function paper(): BelongsTo
    {
        return $this->belongsTo(ResearchPaper::class, 'research_paper_id');
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }
}
