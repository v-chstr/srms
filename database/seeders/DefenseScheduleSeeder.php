<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\DefenseSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class DefenseScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'kummer.marifelgrace@gmail.com')->first();
        if (! $admin) {
            $this->command->warn('DefenseScheduleSeeder: admin user not found. Run UserSeeder first.');
            return;
        }

        $it  = Course::where('code', 'IT')->first();
        $cpe = Course::where('code', 'CpE')->first();

        // Global defense schedule (all programs)
        DefenseSchedule::updateOrCreate(
            ['title' => 'SITE Capstone Defense Orientation'],
            [
                'description'    => 'General orientation for all capstone defense proceedings. Attendance is mandatory.',
                'scheduled_date' => now()->addDays(7)->toDateString(),
                'start_time'     => '09:00:00',
                'end_time'       => '11:00:00',
                'room'           => 'SITE Auditorium',
                'course_id'      => null,
                'created_by'     => $admin->id,
            ]
        );

        // IT-specific defense schedule
        if ($it) {
            DefenseSchedule::updateOrCreate(
                ['title' => 'IT Capstone Defense Day 1'],
                [
                    'description'    => 'Defense schedule for IT capstone groups. Formal attire required.',
                    'scheduled_date' => now()->addDays(14)->toDateString(),
                    'start_time'     => '08:00:00',
                    'end_time'       => '12:00:00',
                    'room'           => 'Room 301, SITE Building',
                    'course_id'      => $it->id,
                    'created_by'     => $admin->id,
                ]
            );
        }

        // CpE-specific defense schedule
        if ($cpe) {
            DefenseSchedule::updateOrCreate(
                ['title' => 'CpE Capstone Defense Day 1'],
                [
                    'description'    => 'Bring printed copies of your manuscript. Defense panel to be announced.',
                    'scheduled_date' => now()->addDays(16)->toDateString(),
                    'start_time'     => '13:00:00',
                    'end_time'       => '17:00:00',
                    'room'           => 'SENG-202',
                    'course_id'      => $cpe->id,
                    'created_by'     => $admin->id,
                ]
            );
        }
    }
}
