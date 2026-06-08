<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRow extends Model
{
    protected $fillable = [
        'group_id',
        'date',
        'activities',
        'attendance',
        'remarks',
        'recorded_by',
    ];

    protected $casts = [
        // Date stays as a string so ranges like "February 18-25, 2026" are valid.
    ];

    public static function normalizeActivities(?string $activities): ?string
    {
        if ($activities === null) {
            return null;
        }

        $activities = str_replace(['<br>', '<br/>', '<br />'], "\n", $activities);
        $activities = str_replace(["\xE2\x80\xA2", "\xC3\xA2\xE2\x82\xAC\xC2\xA2"], '', $activities);

        $normalized = collect(preg_split("/\r\n|\n|\r/", $activities))
            ->map(function (string $line): string {
                $line = trim($line);

                return preg_replace('/^\s*(?:[\x{2022}\-*]+|\d+[\.)])\s+/u', '', $line) ?? $line;
            })
            ->filter(fn (string $line): bool => $line !== '')
            ->implode("\n");

        return $normalized === '' ? null : $normalized;
    }

    public function getFormattedDateAttribute(): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->date)) {
            return Carbon::parse($this->date)->format('M d, Y');
        }

        return $this->date;
    }

    public function getSortTimestampAttribute(): int
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->date)) {
            return Carbon::parse($this->date)->startOfDay()->timestamp;
        }

        if (preg_match('/([A-Za-z]+)\s+(\d{1,2})(?:\s*[-,&]\s*\d{1,2})?,\s*(\d{4})/', $this->date, $matches)) {
            return Carbon::parse(sprintf('%s %s, %s', $matches[1], $matches[2], $matches[3]))->startOfDay()->timestamp;
        }

        return PHP_INT_MAX;
    }

    public function getCleanActivitiesAttribute(): ?string
    {
        return self::normalizeActivities($this->activities);
    }

    public function getAttendanceSummaryAttribute(): string
    {
        if ($this->relationLoaded('studentAttendances') && $this->studentAttendances->isNotEmpty()) {
            $summary = self::buildAttendanceSummary(
                $this->studentAttendances->pluck('status')->all()
            );

            if ($summary !== null) {
                return $summary;
            }
        }

        return $this->attendance ?: 'N/A';
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AttendanceGroup::class, 'group_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function studentAttendances(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function syncStudentAttendanceForGroup(AttendanceGroup $group, array $studentStatuses = []): void
    {
        $group->loadMissing('members');

        $memberIds = $group->members
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        if ($memberIds === []) {
            $this->studentAttendances()->delete();
            $this->forceFill(['attendance' => null])->saveQuietly();

            return;
        }

        $this->studentAttendances()->whereNotIn('user_id', $memberIds)->delete();

        $normalizedStatuses = [];

        foreach ($memberIds as $memberId) {
            $status = AttendanceRecord::normalizeStatus($studentStatuses[$memberId] ?? null);

            $this->studentAttendances()->updateOrCreate(
                ['user_id' => $memberId],
                ['status' => $status]
            );

            $normalizedStatuses[] = $status;
        }

        $this->forceFill([
            'attendance' => self::buildAttendanceSummary($normalizedStatuses),
        ])->saveQuietly();
    }

    protected static function buildAttendanceSummary(array $statuses): ?string
    {
        $summary = collect($statuses)
            ->filter()
            ->countBy()
            ->map(function (int $count, string $status): string {
                $label = AttendanceRecord::STATUS_LABELS[$status] ?? ucfirst($status);

                return $count . ' ' . $label;
            })
            ->values()
            ->implode(', ');

        return $summary !== '' ? $summary : null;
    }
}
