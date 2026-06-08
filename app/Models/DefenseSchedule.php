<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DefenseSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'scheduled_date',
        'start_time',
        'end_time',
        'room',
        'course_id',
        'created_by',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'start_time'     => 'datetime:H:i',
            'end_time'       => 'datetime:H:i',
            'expires_at'     => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ─────────────────────────────────────────────

    /** Active: not expired. If no expires_at, auto-expire 2 weeks after scheduled_date. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where(function ($inner) {
                // Has explicit expiry and it's in the future
                $inner->whereNotNull('expires_at')
                      ->where('expires_at', '>', now());
            })->orWhere(function ($inner) {
                // No explicit expiry: auto-expire 2 weeks after scheduled_date
                $inner->whereNull('expires_at')
                      ->whereRaw('DATE_ADD(scheduled_date, INTERVAL 14 DAY) > ?', [now()]);
            });
        });
    }

    /** Expired: past expiry or past 2 weeks after scheduled_date. */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where(function ($inner) {
                $inner->whereNotNull('expires_at')
                      ->where('expires_at', '<=', now());
            })->orWhere(function ($inner) {
                $inner->whereNull('expires_at')
                      ->whereRaw('DATE_ADD(scheduled_date, INTERVAL 14 DAY) <= ?', [now()]);
            });
        });
    }

    /** Upcoming: scheduled_date is today or in the future, ordered ASC. */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('scheduled_date', '>=', now()->toDateString())
                     ->orderBy('scheduled_date')
                     ->orderBy('start_time');
    }

    /** Scoped for a specific user: global (null course_id) + user's course. */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isStudent()) {
            return $query->where(function ($q) use ($user) {
                $q->whereNull('course_id')
                  ->orWhere('course_id', $user->course_id);
            });
        }

        // Admin and adviser see everything
        return $query;
    }

    // ── Helpers ─────────────────────────────────────────────

    /** Whether this schedule is scoped to a specific course (vs global). */
    public function isGlobal(): bool
    {
        return is_null($this->course_id);
    }
}
