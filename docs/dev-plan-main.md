# SRMS — Master Development Blueprint
### Student Research Management System · School of Information Technology and Engineering (SITE)

> **Direct. Technical. Ready for implementation.**
> Laravel 13 · Blade · TailwindCSS · Alpine.js · Breeze Auth · MySQL (WAMP) · Cloudflare R2

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 (PHP 8.3+) |
| Frontend | Blade Templates + Alpine.js + TailwindCSS |
| Auth | Laravel Breeze (Blade stack) |
| Database | MySQL 8+ via WAMP64 — DB name: `srms-db` |
| Object Storage | Cloudflare R2 (S3 driver) — Bucket: `srms-dev` |
| PDF Export | `barryvdh/laravel-dompdf` |
| Deployment | Laravel Cloud |

---

## Scope

**School:** School of Information Technology and Engineering (SITE)
**Courses served:** IT · CpE · CE · ENSE · BLIS

---

## Phase 1 — Setup & R2 Configuration

### 1.1 `AppServiceProvider` — WAMP 191 Fix

> **Critical.** Without this, every MySQL migration with an indexed `VARCHAR` will fail on WAMP's default charset.

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Schema;

public function boot(): void
{
    Schema::defaultStringLength(191);
}
```

### 1.2 Install Core Dependencies

```bash
# Cloudflare R2 (S3-compatible) driver
composer require league/flysystem-aws-s3-v3 "^3.0" --with-all-dependencies

# PDF export for Admin reports
composer require barryvdh/laravel-dompdf

# Front-end dependencies
npm install
npm run build
```

### 1.3 `.env` Configuration Boilerplate

```dotenv
APP_NAME="SRMS"
APP_ENV=local
APP_KEY=                     # run: php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost/capstone-comm/srms/public

# ─── Database ─────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=srms-db
DB_USERNAME=root
DB_PASSWORD=

# ─── Filesystem ───────────────────────────────────────────
FILESYSTEM_DISK=local        # Change to 'r2' when deploying to Laravel Cloud

# ─── Cloudflare R2 ────────────────────────────────────────
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=auto
AWS_BUCKET=srms-dev
AWS_ENDPOINT=                # https://<ACCOUNT_ID>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true

# ─── Queue & Sessions ─────────────────────────────────────
QUEUE_CONNECTION=database
SESSION_DRIVER=file
CACHE_STORE=file
```

### 1.4 `config/filesystems.php` — R2 Disk

```php
'r2' => [
    'driver'                  => 's3',
    'key'                     => env('AWS_ACCESS_KEY_ID'),
    'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
    'region'                  => env('AWS_DEFAULT_REGION', 'auto'),
    'bucket'                  => env('AWS_BUCKET', 'srms-dev'),
    'endpoint'                => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),
    'throw'                   => true,
    'visibility'              => 'private',
],
```

### 1.5 TailwindCSS — SRMS Design Tokens

```js
// tailwind.config.js (custom extend block)
colors: {
  primary: {
    50:  '#f5f3ff',
    100: '#ede9fe',
    200: '#ddd6fe',
    300: '#c4b5fd',
    400: '#a78bfa',
    500: '#7c3aed',  // main brand purple
    600: '#6d28d9',
    700: '#5b21b6',
    800: '#4c1d95',
    900: '#3b0764',
  },
  accent: {
    50:  '#fffbeb',
    100: '#fef3c7',
    200: '#fde68a',
    300: '#fcd34d',
    400: '#fbbf24',
    500: '#d97706',  // main brand gold
    600: '#b45309',
    700: '#92400e',
  },
  status: {
    pending:  '#6366f1',  // indigo
    revision: '#f59e0b',  // amber
    approved: '#10b981',  // emerald
  },
},
```

### 1.6 R2 API Key Setup Guide

**Step 1 — Log in to Cloudflare Dashboard**
- Visit: https://dash.cloudflare.com → select your account.

**Step 2 — Create the R2 Bucket**
- Left sidebar → **R2 Object Storage** → **Create bucket**
- Name: `srms-dev`, Region: Automatic

**Step 3 — Generate API Token (R2-scoped)**
- Go to **R2 Object Storage** → **Manage R2 API Tokens**
- Create token: Name `srms-dev-token`, Permissions: **Object Read & Write**, Scope: `srms-dev` bucket only

**Step 4 — Copy credentials into `.env`**

| `.env` key | Source |
|---|---|
| `AWS_ACCESS_KEY_ID` | Access Key ID from token page |
| `AWS_SECRET_ACCESS_KEY` | Secret Access Key from token page |
| `AWS_ENDPOINT` | `https://<ACCOUNT_ID>.r2.cloudflarestorage.com` |

**Step 5 — Test the connection**

```bash
php artisan tinker
>>> Storage::disk('r2')->put('test.txt', 'hello R2');
>>> Storage::disk('r2')->delete('test.txt');
```

---

## Phase 2 — Database Schema

### 2.1 Full Schema

**`users`** (extend Breeze default)
```
id, first_name, last_name, email, password, role ENUM('admin','adviser','student'),
is_adviser BOOLEAN DEFAULT FALSE,
course_id (nullable FK → courses.id), email_verified_at, remember_token, timestamps
```

> `role` has 3 values: `admin` (system manager), `adviser` (academic role), and `student`. The `is_adviser` boolean allows admin users to also function as research advisers. Admins with `is_adviser=true` can access both admin panel and adviser features.

**`courses`**
```
id, code VARCHAR(10), name VARCHAR(100),
submission_start DATETIME NULL, submission_end DATETIME NULL, timestamps
```

**`research_papers`**
```
id, title, abstract TEXT, file_path VARCHAR(500),
status ENUM('pending','revision','approved') DEFAULT 'pending',
course_id FK, submitted_by FK → users.id, adviser_id FK → users.id (nullable),
published_year YEAR NULL, views_count INT DEFAULT 0, timestamps
```

**`research_authors`** (pivot — group papers)
```
research_paper_id FK, user_id FK — PRIMARY KEY (research_paper_id, user_id)
```

**`reviews`**
```
id, research_paper_id FK, reviewer_id FK → users.id,
comments TEXT, decision ENUM('revision_required','approved'), timestamps
```

**`defense_schedules`**
```
id, research_paper_id FK, scheduled_date DATE, start_time TIME, room VARCHAR(100), timestamps
```

**`announcements`**
```
id, title, message TEXT, course_id (nullable FK), posted_by FK → users.id, timestamps
```

**`notifications`** — Laravel default (`php artisan notifications:table`)

**`jobs` / `failed_jobs`** — `php artisan queue:table`

### 2.2 Idempotent Seeders Pattern

```php
Course::updateOrCreate(['code' => 'IT'],  ['name' => 'Information Technology']);
Course::updateOrCreate(['code' => 'CpE'], ['name' => 'Computer Engineering']);
Course::updateOrCreate(['code' => 'CE'],  ['name' => 'Civil Engineering']);
Course::updateOrCreate(['code' => 'ENSE'],['name' => 'Environmental and Sanitary Engineering']);
Course::updateOrCreate(['code' => 'BLIS'],['name' => 'Bachelor of Library and Information Science']);
```

---

## Phase 3 — Auth & RBAC

### 3.1 Breeze Installation

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install && npm run build
```

Breeze auth controllers kept. Views replaced with SRMS scholarly design.

### 3.2 Role Middleware

Single parameterized `EnsureRole` middleware at `app/Http/Middleware/EnsureRole.php`.
Accepts variadic roles: `role:admin`, `role:adviser`, `role:student`.
`role:admin` requires `role=admin`. `role:adviser` allows `role=adviser` OR (`role=admin` + `is_adviser=true`). `role:student` requires `role=student`.
Register in `bootstrap/app.php`: `$middleware->alias(['role' => EnsureRole::class])`.

### 3.3 Route Groups

```
GET /                    → redirects to login
GET /dashboard           → single page, role-based content (DashboardController)
```

One `DashboardController` for all roles. Role-specific prefixes (`/admin/*`, `/adviser/*`, `/student/*`) for feature routes.

### 3.4 Test Credentials (Seeder)

| Role | Email | Password | Notes |
|---|---|---|---|
| Admin | `user.admin@srms.site` | `admin1234` | `role=admin`, `is_adviser=true` |
| Teacher | `user.teacher@srms.site` | `teacher1234` | `role=adviser` — adviser/reviews only |
| Student | `user.student@srms.site` | `student1234` | `role=student` |

> Three roles: `admin` (system management), `adviser` (academic advising), `student`. Admins with `is_adviser=true` can also advise.

---

## Phase 4 — Submission & Review Workflow

> **Backend: DONE.** Controllers, service layer, and form requests are fully wired.
> **Views: PENDING.** Blade layouts, components, and page views are the next build target.

### 4.1 Implemented — Backend

**`app/Services/ResearchService.php`** — all business logic lives here:
- `store(array $validated, User $submitter): ResearchPaper` — file upload to configured disk, DB record, author sync
- `update(array $validated, ResearchPaper $paper): ResearchPaper` — replace file if new manuscript provided, reset to `pending`
- `submitReview(ResearchPaper $paper, array $validated, User $reviewer): Review` — creates Review, transitions paper status
- `download(ResearchPaper $paper): RedirectResponse|StreamedResponse` — R2 presigned URL (30 min) or local stream

**Controllers wired:**
- `Student/ResearchController` — index, create, store, show, edit, update, download
- `Adviser/ReviewController` — index, show, store, download
- `Admin/PaperController` — index, show, assignAdviser, download
- `Admin/UserController` — index (with role/search filters), edit (with courses), update
- `Admin/CourseController` — index (with paper count), store, edit, update

**Form Requests:**
- `StoreResearchRequest` — required `title`, `abstract`, `manuscript` (pdf, max 20MB)
- `UpdateResearchRequest` — same rules, `manuscript` nullable (resubmission)

### 4.2 Submission Deadline Guard

Deadline window lives on the `courses` table. Controller checks server-side; Blade disables button client-side.

### 4.3 R2 Upload Flow

Store via `Storage::disk()`. Relative path in DB. See `docs/notes/storage-rules.md`.

### 4.4 Secure Download (Dual-Disk)

R2 → `temporaryUrl()`. Local → `download()`. Both resolved through `FilesystemAdapter` cast.
See `docs/notes/storage-rules.md` and `docs/dev-traits/learn.md` for the `@var FilesystemAdapter` pattern.

### 4.5 Adviser Review Workflow

```
Student submits → status: 'pending'
Adviser reads PDF → submits Review form → status: 'revision' or 'approved'
Student revises → resubmits → back to 'pending'
```

### 4.6 Next — Blade Views Build Order

Build in this order (each depends on the last):
1. `resources/css/app.css` + `tailwind.config.js` — `[DONE]` (tokens, fonts imported)
2. `layouts/guest.blade.php` — login/register shell
3. `layouts/app.blade.php` — authenticated shell with top navigation bar (no sidebar)
4. All `<x-ui.*>`, `<x-form.*>`, `<x-table.*>` base components
5. Auth views (login, register) — replace Breeze defaults
6. `dashboard.blade.php` — role-dispatched content
7. Student pages: research/index, create, show, edit
8. Adviser pages: reviews/index, show (with review form)
9. Admin pages: users/index, users/edit, courses/index, courses/edit, papers/index, papers/show

---

## Phase 5 — Admin Panel

- User management (index, edit role/course)
- Course CRUD
- Paper management (index, show, assign adviser)
- Reports dashboard with stats + PDF export via `barryvdh/laravel-dompdf`

---

## Phase 6 — Archive, Citations & Advanced Features

- **Archive & Search:** Approved papers searchable by keyword, filterable by course
- **Citation Generator:** `CitationService` — APA, MLA, Chicago formats
- **Defense Scheduling:** Adviser sets defense date/time/venue via `defense_schedules` table
- **Announcements:** Admin posts per-course or global announcements
- **Notifications:** Database notifications on status changes

---

## Directory Conventions

```
app/
  Http/
    Controllers/
      DashboardController.php
      Admin/
      Adviser/
      Student/
    Middleware/
      EnsureRole.php
    Requests/
  Models/
  Services/
    ResearchService.php
    CitationService.php

resources/views/
  layouts/
    app.blade.php
    guest.blade.php
  components/
    ui/
    form/
    table/
    layouts/
  pages/
    admin/
    adviser/
    student/
    auth/
  dashboard.blade.php
```

---

## Status Lifecycle

```
pending → revision → approved
            ↑______|
        (student revises)
```

| Status | Color Token | Who sets it |
|---|---|---|
| `pending` | `status-pending` (indigo) | Student (on submit) |
| `revision` | `status-revision` (amber) | Adviser (on review) |
| `approved` | `status-approved` (emerald) | Adviser (on review) |

---

## Hard Rules Summary

1. No business logic in controllers — use `app/Services/`.
2. No N+1 — always `->with([...])` on relations.
3. No `$request->all()` — always `$request->validated()`.
4. No raw `Storage` paths — always use relative paths in DB.
5. No `fake()` in production seeders — guard with `app()->environment('local')`.
6. No raw Tailwind colors for SRMS UI — use `primary-*`, `accent-*`, `status-*` tokens.
7. `Schema::defaultStringLength(191)` in `AppServiceProvider::boot()` — always.
8. Never `migrate:fresh` without explicit user confirmation.
9. Every schema change is a new migration. No squashing.
10. Idempotent seeders — always `updateOrCreate()`.

---

## Artisan Quick Reference

```bash
# Initial Setup
php artisan key:generate
php artisan storage:link
php artisan migrate
php artisan db:seed

# Dev Shell
php artisan tinker

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```
