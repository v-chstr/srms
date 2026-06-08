<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'submission_start',
        'submission_end',
    ];

    protected function casts(): array
    {
        return [
            'submission_start' => 'datetime',
            'submission_end'   => 'datetime',
        ];
    }

    /**
     * Display code with BS prefix for courses that use it.
     * DB stores 'IT', 'CpE', 'CE', 'ENSE', 'BLIS' — display as 'BSIT', 'BSCpE', 'BSCE', 'BSEnSE', 'BLIS'.
     */
    public function displayCode(): string
    {
        return match ($this->code) {
            'IT'   => 'BSIT',
            'CpE'  => 'BSCpE',
            'CE'   => 'BSCE',
            'ENSE' => 'BSEnSE',
            default => $this->code,
        };
    }

    public function researchPapers(): HasMany
    {
        return $this->hasMany(ResearchPaper::class);
    }

    public function defenseSchedules(): HasMany
    {
        return $this->hasMany(DefenseSchedule::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
