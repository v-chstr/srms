<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class AttendanceSection extends Model
{
    protected $fillable = [
        'title',
        'course_id',
        'created_by',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(AttendanceGroup::class, 'section_id')->orderBy('position');
    }

    /** Advisers this section has been shared with (excludes the creator). */
    public function sharedAdvisers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'attendance_section_advisers', 'section_id', 'user_id')
            ->withTimestamps();
    }

    // ──────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────

    /**
     * Sections visible to an adviser: ones they created OR were shared with.
     */
    public function scopeForAdviser(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
            $q->where('created_by', $userId)
              ->orWhereHas('sharedAdvisers', fn (Builder $r) => $r->where('users.id', $userId));
        });
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    /** Whether $userId can edit this section (created it or is a shared adviser). */
    public function isAccessibleBy(int $userId): bool
    {
        if ($this->created_by === $userId) {
            return true;
        }

        return $this->sharedAdvisers->contains('id', $userId);
    }
}
