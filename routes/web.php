<?php

use App\Http\Controllers\UserSearchController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\PaperController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Adviser\AttendanceController;
use App\Http\Controllers\Adviser\AttendanceRowController;
use App\Http\Controllers\Adviser\AnnotationController as AdviserAnnotationController;
use App\Http\Controllers\Adviser\PaperAnnotateController;
use App\Http\Controllers\Adviser\ReviewController;
use App\Http\Controllers\AnnouncementListController;
use App\Http\Controllers\AnnouncementManageController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\Student\AttendanceController as StudentAttendanceController;
use App\Http\Controllers\Student\ResearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('home');

// Archive — public (auth-optional) research library
Route::get('/archive', [ArchiveController::class, 'index'])->name('archive.index');
Route::get('/archive/{id}', [ArchiveController::class, 'show'])->name('archive.show');
Route::get('/archive/{id}/download', [ArchiveController::class, 'download'])->name('archive.download');
Route::get('/archive/{id}/preview', [ArchiveController::class, 'preview'])->name('archive.preview');

Route::middleware(['auth', 'verified', 'no-back'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth', 'no-back'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifications
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications', [NotificationController::class, 'clearAll'])->name('notifications.clear-all');

    // Announcements — index is available to all authenticated users
    Route::get('/announcements', [AnnouncementListController::class, 'index'])->name('announcements.index');

    // User name search — for author-input autocomplete (suggestions only, not restrictive)
    Route::get('/users/search', [UserSearchController::class, 'search'])->name('users.search');

    // Schedule events — all authenticated users can view calendar events
    Route::get('/schedules/events', [ScheduleController::class, 'events'])->name('schedules.events');
});

// Schedule CRUD — restricted to admin + adviser
Route::middleware(['auth', 'role:admin,adviser', 'no-back'])->prefix('schedules')->name('schedules.')->group(function () {
    Route::post('/', [ScheduleController::class, 'store'])->name('store');
    Route::put('{schedule}', [ScheduleController::class, 'update'])->name('update');
    Route::delete('{schedule}', [ScheduleController::class, 'destroy'])->name('destroy');
});

// Queue — adviser only
Route::middleware(['auth', 'role:adviser', 'no-back'])->group(function () {
    Route::prefix('queue')->name('queue.')->group(function () {
        Route::get('/',              [QueueController::class, 'index'])->name('index');
        Route::get('/create',        [QueueController::class, 'create'])->name('create');
        Route::post('/',             [QueueController::class, 'store'])->name('store');
        Route::get('/{queue}',       [QueueController::class, 'show'])->name('show');
        Route::post('/{queue}/next', [QueueController::class, 'next'])->name('next');
        Route::delete('/{queue}',    [QueueController::class, 'destroy'])->name('destroy');
    });

    Route::get('/courses/{course}/students', [QueueController::class, 'courseStudents'])->name('courses.students');
});


// Student routes
Route::middleware(['auth', 'role:student', 'no-back'])->prefix('student')->name('student.')->group(function () {
    Route::get('research', [ResearchController::class, 'index'])->name('research.index');
    Route::post('research', [ResearchController::class, 'store'])->name('research.store');
    Route::put('research/{id}', [ResearchController::class, 'update'])->name('research.update');
    Route::get('research/{id}/download', [ResearchController::class, 'download'])->name('research.download');
    Route::get('research/{id}/preview', [ResearchController::class, 'preview'])->name('research.preview');
    Route::get('research/{id}/annotate', [ResearchController::class, 'annotate'])->name('research.annotate');
    Route::get('research/{id}/annotations', [ResearchController::class, 'annotations'])->name('research.annotations.index');
    Route::get('attendance', [StudentAttendanceController::class, 'index'])->name('attendance.index');
});

// Adviser routes (any admin/staff user can advise)
Route::middleware(['auth', 'role:adviser', 'no-back'])->prefix('adviser')->name('adviser.')->group(function () {
    Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::post('reviews/{paper}', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('reviews/{paper}/download', [ReviewController::class, 'download'])->name('reviews.download');
    Route::get('reviews/{paper}/preview', [ReviewController::class, 'preview'])->name('reviews.preview');
    Route::get('reviews/{paper}/annotate', [PaperAnnotateController::class, 'show'])->name('reviews.annotate');
    Route::post('reviews/{paper}/annotate', [PaperAnnotateController::class, 'submit'])->name('reviews.annotate.submit');
    Route::get('reviews/{paper}/annotations', [AdviserAnnotationController::class, 'index'])->name('reviews.annotations.index');
    Route::post('reviews/{paper}/annotations', [AdviserAnnotationController::class, 'store'])->name('reviews.annotations.store');
    Route::patch('reviews/{paper}/annotations/{annotation}', [AdviserAnnotationController::class, 'update'])->name('reviews.annotations.update');
    Route::delete('reviews/{paper}/annotations/{annotation}', [AdviserAnnotationController::class, 'destroy'])->name('reviews.annotations.destroy');

    Route::get('announcements', [AnnouncementManageController::class, 'index'])->name('announcements.index');
    Route::post('announcements', [AnnouncementManageController::class, 'store'])->name('announcements.store');
    Route::put('announcements/{announcement}', [AnnouncementManageController::class, 'update'])->name('announcements.update');
    Route::delete('announcements/{announcement}', [AnnouncementManageController::class, 'destroy'])->name('announcements.destroy');

    // Class Attendance
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/',             [AttendanceController::class, 'index'])->name('index');
        Route::get('/create',       [AttendanceController::class, 'create'])->name('create');
        Route::post('/',            [AttendanceController::class, 'store'])->name('store');
        Route::get('/{section}',    [AttendanceController::class, 'show'])->name('show');
        Route::delete('/{section}', [AttendanceController::class, 'destroy'])->name('destroy');

        Route::put('/rows/{row}',      [AttendanceRowController::class, 'update'])->name('rows.update');

        Route::post('/groups/{group}/members', [AttendanceController::class, 'addMember'])->name('groups.members.store');
    });
});

// Admin routes
Route::middleware(['auth', 'role:admin', 'no-back'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::post('users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
    Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
    Route::put('courses/{course}', [CourseController::class, 'update'])->name('courses.update');

    Route::get('announcements', [AnnouncementManageController::class, 'index'])->name('announcements.index');
    Route::post('announcements', [AnnouncementManageController::class, 'store'])->name('announcements.store');
    Route::put('announcements/{announcement}', [AnnouncementManageController::class, 'update'])->name('announcements.update');
    Route::delete('announcements/{announcement}', [AnnouncementManageController::class, 'destroy'])->name('announcements.destroy');

    Route::get('papers', [PaperController::class, 'index'])->name('papers.index');
    Route::post('papers', [PaperController::class, 'store'])->name('papers.store');
    Route::get('papers/{paper}/download', [PaperController::class, 'download'])->name('papers.download');
    Route::get('papers/{paper}/preview', [PaperController::class, 'preview'])->name('papers.preview');
    Route::delete('papers/{paper}', [PaperController::class, 'destroy'])->name('papers.destroy');
});

require __DIR__.'/auth.php';
