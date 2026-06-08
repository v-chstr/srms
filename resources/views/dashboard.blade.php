<x-layouts.app title="Dashboard">

    <div class="space-y-4">

        {{-- ── Announcement banner (all roles) ──────────────────── --}}
        @if($latestAnnouncement)
            @php
                $announcementsUrl = match(true) {
                    auth()->user()->isAdmin()   => route('admin.announcements.index'),
                    auth()->user()->canAdvise() => route('adviser.announcements.index'),
                    default                     => route('announcements.index'),
                };
            @endphp
            <div class="rounded-md border border-gray-200 bg-white shadow-sm overflow-hidden">
                {{-- Top strip: label + scope + date --}}
                <div class="px-4 py-2 bg-primary-700 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        {{-- Breathing live indicator --}}
                        <span class="relative flex h-2 w-2 shrink-0">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-accent-400"></span>
                        </span>
                        <span class="text-xs font-semibold text-white">Announcement</span>
                        @if($latestAnnouncement->course)
                            <span class="text-xs font-medium text-primary-900 bg-accent-400 px-2 py-0.5 rounded-md">{{ $latestAnnouncement->course->displayCode() }}</span>
                        @else
                            <span class="text-xs font-medium text-white/80 bg-white/15 px-2 py-0.5 rounded-md">All departments</span>
                        @endif
                        @if($unseenAnnouncementCount > 0)
                            <span class="text-xs font-semibold text-primary-900 bg-accent-400 px-2 py-0.5 rounded-md tabular-nums">{{ $unseenAnnouncementCount }} unread</span>
                        @endif
                    </div>
                    <span class="text-xs text-primary-300 shrink-0">{{ $latestAnnouncement->created_at->format('M j, Y') }}</span>
                </div>
                {{-- Body --}}
                <div class="px-4 py-3 flex flex-col gap-1">
                    <p class="text-base font-semibold text-gray-900 leading-snug">{{ $latestAnnouncement->title }}</p>
                    @if($latestAnnouncement->poster)
                        <p class="text-xs text-gray-500">Posted by {{ trim($latestAnnouncement->poster->first_name . ' ' . $latestAnnouncement->poster->last_name) }}</p>
                    @endif
                    <p class="text-sm text-gray-600 line-clamp-2 leading-relaxed mt-0.5">{{ $latestAnnouncement->message }}</p>
                    <a href="{{ $announcementsUrl }}" data-nav-link class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-primary-700 hover:text-primary-900 self-start">
                        <span>View all announcements</span>
                        <x-ui.icon name="arrow-right" size="2xs" />
                    </a>
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════ --}}
        {{-- ── ADMIN DASHBOARD ───────────────────────────────── --}}
        {{-- ══════════════════════════════════════════════════════ --}}
        @if($role === 'admin')

            {{-- Defense Calendar: full-width for admins --}}
            <x-ui.schedule-calendar :events="$scheduleEvents" :canCreate="true" />
            <x-ui.schedule-modal :courses="$courses" />
            @foreach($schedules as $s)
                <x-ui.schedule-modal :schedule="$s" :courses="$courses" />
            @endforeach

            {{-- Bento: recent submissions + pending accounts (left 2/3) / papers by program (right 1/3) --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">

                {{-- Left 2/3: submissions + pending accounts stacked --}}
                <div class="lg:col-span-2 flex flex-col gap-4">

                    {{-- Recent Submissions --}}
                    @if($recentPapers->isEmpty())
                        <x-ui.empty-state title="Recent submissions" message="No research submissions yet." :cta="route('admin.papers.index')" ctaLabel="View all" />
                    @else
                    <x-ui.card title="Recent submissions" :compact="true"
                        :viewAllRoute="route('admin.papers.index')">
                        <x-table.wrapper class="border-0 rounded-none">
                            <x-slot:head>
                                <x-table.heading>Title</x-table.heading>
                                <x-table.heading class="hidden sm:table-cell w-36">Student</x-table.heading>
                                <x-table.heading class="hidden sm:table-cell w-24">Course</x-table.heading>
                                <x-table.heading class="w-28">Status</x-table.heading>
                                <x-table.heading class="hidden lg:table-cell w-24 text-right">Date</x-table.heading>
                            </x-slot:head>
                            @foreach($recentPapers as $paper)
                                <tr>
                                    <x-table.cell wrap>{{ $paper->title }}</x-table.cell>
                                    <x-table.cell class="hidden sm:table-cell" wrap>{{ $paper->submitter?->first_name }} {{ $paper->submitter?->last_name }}</x-table.cell>
                                    <x-table.cell class="hidden sm:table-cell" nowrap>{{ $paper->course?->displayCode() ?? 'N/A' }}</x-table.cell>
                                    <x-table.cell nowrap><x-ui.badge :status="$paper->status" /></x-table.cell>
                                    <x-table.cell class="hidden lg:table-cell text-right text-gray-400" nowrap><x-ui.date :value="$paper->created_at" short /></x-table.cell>
                                </tr>
                            @endforeach
                        </x-table.wrapper>
                    </x-ui.card>
                    @endif

                    {{-- Pending Account Requests --}}
                    @if($pendingUsers->isEmpty())
                        <x-ui.empty-state title="Pending account requests" message="No pending accounts." :cta="route('admin.users.index')" ctaLabel="View users" />
                    @else
                    <x-ui.card title="Pending account requests" :compact="true"
                        :viewAllRoute="route('admin.users.index')"
                        maxHeight="max-h-56">
                        <div class="divide-y divide-gray-50">
                            @foreach($pendingUsers as $pUser)
                                <div class="flex items-center gap-3 px-4 py-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-500">
                                        {{ strtoupper(substr($pUser->first_name, 0, 1)) }}{{ strtoupper(substr($pUser->last_name, 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $pUser->first_name }} {{ $pUser->last_name }}</p>
                                        <p class="text-xs text-gray-400 truncate">
                                            {{ $pUser->email }}
                                            @if($pUser->course) / {{ $pUser->course->displayCode() }} @endif
                                        </p>
                                    </div>
                                    <span class="shrink-0 text-xs font-semibold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded-md">Pending</span>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>
                    @endif

                </div>

                {{-- Right 1/3: papers by program --}}
                <div class="lg:col-span-1">
                    @php $maxPapers = $courseOutput->max('count') ?: 1; @endphp
                    @if($courseOutput->isEmpty())
                        <x-ui.empty-state title="Papers by program" message="No papers submitted yet." />
                    @else
                    <x-ui.card title="Papers by program" :compact="true">
                        <div class="grid grid-cols-2 gap-px bg-gray-100">
                            @foreach($courseOutput as $course)
                                <div class="bg-white px-4 py-3">
                                    <div class="flex items-baseline justify-between mb-1.5">
                                        <span class="text-sm font-semibold text-gray-800">{{ $course['code'] }}</span>{{-- displayCode() applied in controller --}}
                                        <span class="text-xs tabular-nums font-medium text-gray-400">{{ $course['count'] }}</span>
                                    </div>
                                    <div class="h-1 bg-gray-100 overflow-hidden rounded-full">
                                        <div class="h-full bg-primary-500" style="width: {{ $course['count'] > 0 ? round(($course['count'] / $maxPapers) * 100) : 0 }}%"></div>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-400 truncate">{{ $course['name'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>
                    @endif
                </div>

            </div>

            {{-- Quick Actions --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-4 gap-3">
                <x-ui.action-card :href="route('admin.users.index')" title="Users" description="Manage student and adviser accounts." />
                <x-ui.action-card :href="route('admin.courses.index')" title="Courses" description="Add or update department programs." />
                <x-ui.action-card :href="route('admin.papers.index')" title="Papers" description="Review and assign advisers to submissions." />
                <x-ui.action-card :href="route('admin.announcements.index')" title="Announcements" description="Post notices to students and advisers." />

            </div>

        {{-- ══════════════════════════════════════════════════════ --}}
        {{-- ── ADVISER DASHBOARD ─────────────────────────────── --}}
        {{-- ══════════════════════════════════════════════════════ --}}
        @elseif($role === 'adviser')

            {{-- Defense Calendar (full width) --}}
            <x-ui.schedule-calendar :events="$scheduleEvents" :canCreate="true" />
            <x-ui.schedule-modal :courses="$courses" />
            @foreach($schedules as $s)
                <x-ui.schedule-modal :schedule="$s" :courses="$courses" />
            @endforeach

            {{-- Recent Submissions --}}
            @if($recentPapers->isEmpty())
                <x-ui.empty-state title="Recent submissions" message="No papers assigned yet." :cta="route('adviser.reviews.index')" ctaLabel="Open reviews" />
            @else
            <x-ui.card title="Recent submissions" :compact="true"
                :viewAllRoute="route('adviser.reviews.index')">
                <x-table.wrapper class="border-0 rounded-none">
                    <x-slot:head>
                        <x-table.heading>Title</x-table.heading>
                        <x-table.heading class="hidden sm:table-cell w-36">Author</x-table.heading>
                        <x-table.heading class="w-28">Status</x-table.heading>
                        <x-table.heading class="hidden lg:table-cell w-24 text-right">Date</x-table.heading>
                    </x-slot:head>
                    @foreach($recentPapers as $paper)
                        <tr>
                            <x-table.cell wrap>
                                <span class="font-medium text-gray-900 break-words">{{ $paper->title }}</span>
                            </x-table.cell>
                            <x-table.cell class="hidden sm:table-cell text-gray-500" wrap>
                                {{ $paper->submitter ? trim($paper->submitter->first_name . ' ' . $paper->submitter->last_name) : 'N/A' }}
                            </x-table.cell>
                            <x-table.cell nowrap><x-ui.badge :status="$paper->status" /></x-table.cell>
                            <x-table.cell class="hidden lg:table-cell text-right text-gray-400" nowrap><x-ui.date :value="$paper->created_at" short /></x-table.cell>
                        </tr>
                    @endforeach
                </x-table.wrapper>
            </x-ui.card>
            @endif

            {{-- Quick Actions --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <x-ui.action-card :href="route('adviser.reviews.index')" title="Review Papers" description="Open the review queue and submit feedback on assigned papers." />
                <x-ui.action-card :href="route('queue.index')" title="Defense Queue" description="Manage and run the oral defense queue for scheduled sessions." />
                <x-ui.action-card :href="route('adviser.attendance.index')" title="Class Attendance" description="Record group attendance and keep the student view in sync." />
                <x-ui.action-card :href="route('adviser.announcements.index')" title="Announcements" description="Post and manage announcements for your assigned courses." />
            </div>

        {{-- ══════════════════════════════════════════════════════ --}}
        {{-- ── STUDENT DASHBOARD ─────────────────────────────── --}}
        {{-- ══════════════════════════════════════════════════════ --}}
        @elseif($role === 'student')

            {{-- Queue widget — shown when student is assigned to an active or pending queue --}}
            @if($activeQueueGroup)
                <x-student.queue-widget :group="$activeQueueGroup" />
            @endif

            {{-- Defense Calendar: full-width --}}
            <x-ui.schedule-calendar :events="$scheduleEvents" :canCreate="false" />

            {{-- My Submissions + Archive CTA: side-by-side bento --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-stretch">

                {{-- My Submissions --}}
                <div class="flex flex-col h-full">
                    @if($recentPapers->isEmpty())
                        <div class="bg-white border border-gray-200 rounded-md shadow-sm overflow-hidden h-full flex flex-col justify-between">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <p class="text-sm font-semibold text-gray-900">My submissions</p>
                                <a href="{{ route('student.research.index') }}" data-nav-link class="text-xs font-semibold text-primary-700 hover:text-primary-900">View all</a>
                            </div>
                            <div class="px-4 py-8 flex flex-col items-center gap-3 text-center flex-1 justify-center">
                                <p class="text-sm text-gray-500">No research papers submitted yet.</p>
                                <a href="{{ route('student.research.index') }}" data-nav-link
                                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-md bg-primary-700 text-sm font-semibold text-white hover:bg-primary-800">
                                    Submit your first paper
                                    <x-ui.icon name="arrow-right" class="h-4 w-4" />
                                </a>
                            </div>
                        </div>
                    @else
                    <x-ui.card title="My submissions" :compact="true"
                        :viewAllRoute="route('student.research.index')"
                        class="h-full">
                        <x-table.wrapper class="border-0 rounded-none">
                            <x-slot:head>
                                <x-table.heading>Title</x-table.heading>
                                <x-table.heading class="w-28">Status</x-table.heading>
                                <x-table.heading class="hidden xl:table-cell w-24 text-right">Date</x-table.heading>
                            </x-slot:head>
                            @foreach($recentPapers as $paper)
                                <tr>
                                    <x-table.cell wrap>{{ $paper->title }}</x-table.cell>
                                    <x-table.cell nowrap><x-ui.badge :status="$paper->status" /></x-table.cell>
                                    <x-table.cell class="hidden xl:table-cell text-right text-gray-400" nowrap><x-ui.date :value="$paper->created_at" short /></x-table.cell>
                                </tr>
                            @endforeach
                        </x-table.wrapper>
                    </x-ui.card>
                    @endif
                </div>

                {{-- My Attendance --}}
                <div class="flex flex-col h-full">
                    <a href="{{ route('student.attendance.index') }}" data-nav-link
                       class="flex flex-col rounded-md border border-gray-200 overflow-hidden hover:border-primary-300 shadow-sm h-full justify-between">
                        <div class="bg-white px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-gray-900">My Attendance</span>
                            <x-ui.icon name="clipboard-document-check" class="h-4 w-4 text-primary-600" />
                        </div>
                        <div class="bg-gray-50/70 px-4 py-4 flex flex-col gap-4 flex-1 justify-between">
                            <div class="grid grid-cols-2 gap-2 text-center">
                                <div class="rounded-md bg-emerald-50 border border-emerald-100 py-2 px-1">
                                    <span class="block text-[10px] font-medium text-emerald-700">Present</span>
                                    <span class="block mt-0.5 text-base font-bold text-emerald-800">{{ $attendanceStats['present'] }}</span>
                                </div>
                                <div class="rounded-md bg-rose-50 border border-rose-100 py-2 px-1">
                                    <span class="block text-[10px] font-medium text-rose-700">Absent</span>
                                    <span class="block mt-0.5 text-base font-bold text-rose-800">{{ $attendanceStats['absent'] }}</span>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-md bg-primary-700 text-sm font-semibold text-white self-start">
                                Open Attendance
                                <x-ui.icon name="arrow-right" class="h-4 w-4" />
                            </span>
                        </div>
                    </a>
                </div>

                {{-- Research Archive --}}
                <div class="flex flex-col h-full">
                    <a href="{{ route('archive.index') }}" data-nav-link
                       class="flex flex-col rounded-md border border-primary-200 overflow-hidden hover:border-primary-300 h-full justify-between">
                        <div class="bg-primary-700 px-4 py-2.5 flex items-center justify-between gap-2">
                            <span class="text-xs font-semibold text-white">Research Archive</span>
                            <span class="text-xs font-medium text-primary-900 bg-accent-400 px-2 py-0.5 rounded-md">For RRL</span>
                        </div>
                        <div class="bg-primary-50/70 px-4 py-4 flex flex-col gap-4 flex-1 justify-between">
                            <p class="text-sm text-primary-900 leading-relaxed">Browse approved theses and papers from SITE. Find related works to build your review of related literature.</p>
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-md bg-primary-700 text-sm font-semibold text-white self-start">
                                Browse Archive
                                <x-ui.icon name="arrow-right" class="h-4 w-4" />
                            </span>
                        </div>
                    </a>
                </div>

            </div>

        @endif

    </div>

</x-layouts.app>
