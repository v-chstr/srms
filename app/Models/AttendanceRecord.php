<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_LATE = 'late';
    public const STATUS_EXCUSED = 'excused';

    public const STATUS_LABELS = [
        self::STATUS_PRESENT => 'Present',
        self::STATUS_ABSENT => 'Absent',
        self::STATUS_LATE => 'Late',
        self::STATUS_EXCUSED => 'Excused',
    ];

    protected $fillable = [
        'attendance_row_id',
        'user_id',
        'status',
    ];

    public static function allowedStatuses(): array
    {
        return array_keys(self::STATUS_LABELS);
    }

    public static function normalizeStatus(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $normalized = strtolower(trim($status));

        return match (true) {
            $normalized === '' => null,
            str_contains($normalized, 'absent') => self::STATUS_ABSENT,
            str_contains($normalized, 'late') => self::STATUS_LATE,
            str_contains($normalized, 'excused') => self::STATUS_EXCUSED,
            str_contains($normalized, 'present') => self::STATUS_PRESENT,
            default => null,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Not set';
    }

    public function attendanceRow(): BelongsTo
    {
        return $this->belongsTo(AttendanceRow::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
