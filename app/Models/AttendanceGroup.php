<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceGroup extends Model
{
    protected $fillable = [
        'section_id',
        'position',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function section(): BelongsTo
    {
        return $this->belongsTo(AttendanceSection::class, 'section_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'attendance_group_members', 'group_id', 'user_id')
            ->withTimestamps();
    }

    public function rows(): HasMany
    {
        return $this->hasMany(AttendanceRow::class, 'group_id')->orderBy('id');
    }

    /**
     * Parse the capstone_project2_timeline.md template and populate default rows.
     */
    public function createDefaultTimelineRows(): void
    {
        $filePath = base_path('capstone_project2_timeline.md');
        if (! file_exists($filePath)) {
            return;
        }

        $this->loadMissing('members');

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '|')) {
                if (str_contains($line, 'Date') && str_contains($line, 'Activities')) {
                    continue;
                }
                if (str_contains($line, '---') || preg_match('/^\|\s*[:-]-+[:-]\s*\|/', $line)) {
                    continue;
                }

                $cols = explode('|', $line);
                if (count($cols) >= 4) {
                    $date = trim($cols[1]);
                    $activities = trim($cols[2]);

                    $activities = AttendanceRow::normalizeActivities($activities);

                    $row = $this->rows()->create([
                        'date'       => $date,
                        'activities' => $activities,
                        'attendance' => null,
                        'remarks'    => null,
                        'recorded_by' => null,
                    ]);

                    $row->syncStudentAttendanceForGroup($this);
                }
            }
        }
    }
}
