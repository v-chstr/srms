<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $it = Course::where('code', 'IT')->first();

        $admin = User::where('email', 'kummer.marifelgrace@gmail.com')->first();

        // Global announcement
        Announcement::updateOrCreate(
            [
                'title'     => 'Research Submission Deadline',
                'posted_by' => $admin?->id,
            ],
            [
                'message'   => 'All students are reminded that the final deadline for research paper submission is on May 31, 2025 at 5:00 PM. Papers must be submitted through the SRMS portal in PDF format. Late submissions will not be accepted unless a written extension request has been approved by the Research Coordinator at least five (5) working days before the deadline.',
                'course_id' => null,
            ]
        );

        // IT-specific announcement
        Announcement::updateOrCreate(
            [
                'title'     => 'IT Research Colloquium Schedule',
                'posted_by' => $admin?->id,
            ],
            [
                'message'   => 'The School of Information Technology and Engineering will hold its Research Colloquium on May 14-16, 2025 at the SIT Building, Rooms 301 to 304. IT students whose papers have been approved for oral defense are required to report to the Registrar\'s Office no later than May 10 to confirm their attendance. Defense panels will be announced on the SRMS portal by May 8.',
                'course_id' => $it?->id,
            ]
        );

        // System maintenance announcement
        Announcement::updateOrCreate(
            [
                'title'     => 'System Maintenance Notice',
                'posted_by' => $admin?->id,
            ],
            [
                'message'   => 'The SRMS portal will be unavailable on Saturday, April 12, 2025 from 12:00 AM to 4:00 AM due to scheduled database maintenance and server patching. Please ensure all pending submissions and reviews are completed before the maintenance window.',
                'course_id' => null,
            ]
        );
    }
}
