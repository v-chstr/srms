<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'status',
        'is_adviser',
        'course_id',
        'last_announcement_read_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'          => 'datetime',
            'password'                   => 'hashed',
            'is_adviser'                 => 'boolean',
            'last_announcement_read_at'  => 'datetime',
        ];
    }

    // Role helpers

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function canAdvise(): bool
    {
        return $this->role === 'adviser'
            || ($this->role === 'admin' && $this->is_adviser);
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    // Scopes

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeAdvisers(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(fn (Builder $q) => $q
                ->where('role', 'adviser')
                ->orWhere(fn (Builder $q) => $q->where('role', 'admin')->where('is_adviser', true))
            );
    }

    // Relationships

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function submittedPapers(): HasMany
    {
        return $this->hasMany(ResearchPaper::class, 'submitted_by');
    }

    public function advisedPapers(): HasMany
    {
        return $this->hasMany(ResearchPaper::class, 'adviser_id');
    }

    public function authoredPapers(): BelongsToMany
    {
        return $this->belongsToMany(ResearchPaper::class, 'research_authors');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
