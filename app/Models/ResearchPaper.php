<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResearchPaper extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'abstract',
        'keywords',
        'file_path',
        'original_filename',
        'status',
        'course_id',
        'submitted_by',
        'adviser_id',
        'published_year',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
        ];
    }

    // Relationships

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function authors(): HasMany
    {
        return $this->hasMany(ResearchAuthor::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(Annotation::class)->orderBy('created_at');
    }

    // Query Scopes

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeRevision(Builder $query): Builder
    {
        return $query->where('status', 'revision');
    }

    public function scopeForCourse(Builder $query, int $courseId): Builder
    {
        return $query->where('course_id', $courseId);
    }
}
