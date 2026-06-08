# SRMS Audit - Course Extensibility & Blade Cleanup

Generated: 2026-04-11
Updated: 2026-04-11 (post-cleanup)

## Executive Summary

Adding a new course is safe in the runtime application. The live backend reads courses dynamically everywhere.

Operational weaknesses found:

1. Seeded environments are tied to the original five courses.
2. Submission window fields exist in schema but are not enforced anywhere.

Blade cleanup completed:

- 6 dead files deleted (3 dashboard partials, 2 unused nav components, 1 unused pagination component)
- 1 empty directory removed (`pages/dashboard/`)
- Remaining structure is clean, intentional, and well-organized

## Additional Findings Beyond the First Course Audit

### 1. Runtime course propagation is strong

The application layer is generally dynamic rather than hard-coded.

- New courses are created through app/Http/Controllers/Admin/CourseController.php.
- Registration pulls the course list at request time in app/Http/Controllers/Auth/RegisteredUserController.php.
- Student submissions inherit the student's assigned course in app/Services/ResearchService.php.
- Reports and dashboard aggregates use Course::withCount(...) in app/Http/Controllers/DashboardController.php and app/Http/Controllers/Admin/ReportController.php.
- Admin, adviser, and archive filters read fresh course data from the database in app/Http/Controllers/Admin/PaperController.php, app/Http/Controllers/Adviser/ReviewController.php, app/Http/Controllers/Admin/AnnouncementController.php, app/Http/Controllers/AnnouncementListController.php, app/Http/Controllers/ScheduleController.php, and app/Http/Controllers/ArchiveController.php.

Practical result: if a course is added to an existing database, it usually appears everywhere immediately.

### 2. Seeded environments are not extensible in the same way

The runtime app is dynamic, but the seeded setup is fixed to the original course set.

- database/seeders/CourseSeeder.php seeds only IT, CpE, CE, ENSE, and BLIS.
- database/seeders/StudentSeeder.php looks up those same five codes explicitly.
- database/seeders/ResearchPaperSeeder.php looks up IT, CpE, CE, and BLIS explicitly.
- database/seeders/AnnouncementSeeder.php looks up IT and CpE explicitly.
- database/seeders/DefenseScheduleSeeder.php looks up IT and CpE explicitly.

Practical result:

- On an existing database, manually added courses survive db:seed because seeders use updateOrCreate.
- On a fresh environment or migrate:fresh --seed, only the original seeded course set exists unless the seeders are updated.

This is not a runtime failure. It is a setup and environment drift problem.

### 3. Submission windows exist in schema only

The courses table supports submission_start and submission_end, and the Course model casts them, but the backend never uses them.

- database/migrations/2026_04_09_000001_create_courses_table.php
- app/Models/Course.php
- app/Http/Controllers/Admin/CourseController.php does not validate or persist them
- app/Http/Requests/StoreResearchRequest.php and app/Http/Requests/UpdateResearchRequest.php do not enforce them
- app/Services/ResearchService.php does not check them

Practical result: adding a new course with its own submission timeline is not really supported, even though the schema suggests it is.

### 4. Adviser assignment rules are weaker than the UI implies

Student paper creation and update only require that adviser_id exists.

- app/Http/Requests/StoreResearchRequest.php
- app/Http/Requests/UpdateResearchRequest.php

The stronger canAdvise check exists only in admin paper reassignment:

- app/Http/Controllers/Admin/PaperController.php

Also, adviser course assignment remains optional:

- app/Http/Controllers/Admin/UserController.php

Practical result: the system is flexible, but not strict. A new course can function even if adviser-course policy is loose, because the backend does not enforce strong course-adviser matching.

### 5. There is almost no automated safety net for this area

Current tests do not cover course onboarding, course propagation, or Blade management flows.

- tests/Feature/ExampleTest.php
- tests/Feature/ProfileTest.php
- tests/Unit/ExampleTest.php

Practical result: regressions in course creation, filters, modals, and new-course propagation would likely be caught manually, not automatically.

### 6. One unrelated route inconsistency was found

app/Notifications/ResearchReviewed.php generates a route to student.research.show, but that route is not defined in routes/web.php.

This is not caused by adding a course, but it is a real backend inconsistency discovered during the audit.

## Blade Cleanup - Completed

### Dead files removed

These files were confirmed dead (zero references in any controller, route, or Blade file) and deleted:

| File | Why it was dead |
|---|---|
| `components/layouts/nav-link.blade.php` | Never called as `<x-layouts.nav-link>`. Leftover from a top-nav architecture that was replaced by sidebar. |
| `components/layouts/mobile-nav-link.blade.php` | Never called. Same as above. |
| `components/ui/pagination.blade.php` | Never called as `<x-ui.pagination>`. Laravel's built-in `->links()` is used instead. |
| `pages/dashboard/admin.blade.php` | Never rendered by DashboardController. Role-specific content lives inline in `dashboard.blade.php`. |
| `pages/dashboard/adviser.blade.php` | Same as above. |
| `pages/dashboard/student.blade.php` | Same as above. |
| `pages/dashboard/` (directory) | Empty after partial removal. |

### What is NOT overengineered (previous audit corrections)

**Per-record modals are intentional and correct.** The modal-per-record pattern across all 6 index pages is the project's standard CRUD architecture per CLAUDE.md section 4a. It works well for paginated datasets and keeps all actions on the index page. Not overengineered.

**Two card components serve different purposes.** `x-ui.card` (20+ uses) is a generic wrapper with title, slot, and footer. `x-dashboard.card` (12 uses) is a specialized dashboard section with scrollable content, empty states, and view-all links. These are correctly separated.

**Announcements have two surfaces for different audiences.** `/announcements` is the shared feed (students read, advisers create/edit). `/admin/announcements` is the admin management console with filtering, search, and archiving. Both are actively wired, serve different roles, and use the same `Announcement` model. The page-based create/edit forms for the shared surface are appropriate because advisers need them and advisers do not have access to the admin panel.

## Current Blade Structure (Post-Cleanup)

### components/ (42 files)

| Folder | Count | Purpose |
|---|---|---|
| `ui/` | 11 | Generic primitives: alert, badge, button, card, date, detail-field, modal, modal-footer, modal-header, modal-shell, stat-card |
| `form/` | 6 | Form fields: checkbox, error, file, input, select, textarea |
| `table/` | 4 | Table scaffold: cell, empty, heading, wrapper |
| `layouts/` | 6 | App shell, guest shell, notifications-modal, page-header, sidebar, sidebar-link |
| `dashboard/` | 5 | Dashboard composites: announcement-banner, calendar, card, schedule-modal, stats-grid |
| `admin/` | 4 | Admin modals: announcement, course, paper-actions, user-actions |
| `adviser/` | 1 | Adviser modal: review-actions |
| `student/` | 2 | Student modals: research-actions, research-create |
| `auth/` | 5 | Auth components: forgot-password-modal, hero-panel, login-form, register-form, spup-footer |

### pages/ (14 files)

| Path | Purpose |
|---|---|
| `admin/users/index.blade.php` | Admin user management |
| `admin/courses/index.blade.php` | Admin course management |
| `admin/papers/index.blade.php` | Admin paper management |
| `admin/announcements/index.blade.php` | Admin announcement management (modal CRUD) |
| `admin/reports/index.blade.php` | Admin reports dashboard |
| `admin/reports/pdf.blade.php` | PDF export template |
| `adviser/reviews/index.blade.php` | Adviser review queue |
| `student/research/index.blade.php` | Student research management |
| `announcements/index.blade.php` | Shared announcement feed (all roles) |
| `announcements/create.blade.php` | Create announcement (admin + adviser) |
| `announcements/edit.blade.php` | Edit announcement (admin + adviser) |
| `notifications/index.blade.php` | Notifications page |
| `archive/index.blade.php` | Public research archive |
| `archive/show.blade.php` | Single paper archive view |

### Top-level views

| File | Purpose |
|---|---|
| `dashboard.blade.php` | Role-dispatched dashboard (all roles, single file) |
| `welcome.blade.php` | Landing page |

## Folder Organization Assessment

### What is working well

- `pages/` grouped by domain and role is clean and predictable
- `components/ui/`, `components/form/`, `components/table/` are proper separations
- `components/admin/`, `components/student/`, `components/adviser/` isolate role-specific modal logic
- `components/dashboard/` groups dashboard-only composites without cluttering `ui/`
- `components/auth/` separates auth-specific components from the app shell
- Every component is actively used (no dead components remain)

### No remaining dead files or structural issues

The cleanup removed all identified dead weight. The remaining structure has no unused files, no duplicate surfaces serving the same purpose, and clear folder boundaries.
