# learn.md — Lessons, Gotchas & Discoveries

> **Purpose:** A living document that captures bugs, unexpected behaviors, hard-won fixes, and "don't do this again" lessons.
> **AI Agents:** Read this before implementing any feature. If you encounter a new issue, append it here.

---

## Format

Each entry follows this structure:

```
### [SHORT TITLE]
- **Encountered:** Date / phase
- **Symptom:** What went wrong (observable behavior)
- **Root Cause:** Why it happened
- **Fix:** Exact resolution
- **Prevention Rule:** What to check next time
```

---

## Database & Migrations

### MySQL VARCHAR Index Length Failure
- **Encountered:** Initial setup — Laravel 13 + MySQL 8 on WAMP64
- **Symptom:** `Specified key was too long; max key length is 1000 bytes` error on `php artisan migrate`
- **Root Cause:** MySQL's default character set `utf8mb4` uses 4 bytes per character; default string column length of 255 x 4 = 1020 bytes exceeds InnoDB's 767-byte index limit
- **Fix:** Add `Schema::defaultStringLength(191)` to `AppServiceProvider::boot()`
- **Prevention Rule:** Always verify `AppServiceProvider` has this before running migrations. It must be set BEFORE the first `migrate` call. This applies to both WAMP64 (local) AND Laravel Cloud (production).

---

### Migration Order Dependency (FK Constraint Failure)
- **Encountered:** Schema creation phase
- **Symptom:** `SQLSTATE[HY000]: General error: 1215 Cannot add foreign key constraint` when migrating tables with foreign keys
- **Root Cause:** Child table migration has a timestamp earlier than (or equal to) its parent table migration. Laravel runs migrations in timestamp order — if the parent doesn't exist yet, the FK constraint fails.
- **Fix:** Ensure migration filenames follow dependency order. Parent tables must have earlier timestamps than child tables: `users` → `courses` → `research_papers` → `research_authors` → `reviews` → `defense_schedules` → `announcements`.
- **Prevention Rule:** When creating migrations with FKs, generate them in dependency order. Use `php artisan migrate:status` to verify order. Wrap every migration in FK safety guards (`Schema::disableForeignKeyConstraints()` / `Schema::enableForeignKeyConstraints()`).

---

### PHP Attribute Syntax `#[Fillable]` — Avoid This
- **Encountered:** Initial model creation
- **Symptom:** Complex model expansion (adding relationships, additional fields) is harder to maintain using PHP 8 Attribute syntax
- **Root Cause:** The PHP 8 Attribute approach is compact but not composable — you can't conditionally append fillable fields or merge arrays easily
- **Fix:** Use `protected $fillable = []` array syntax on all models
- **Prevention Rule:** Never use `#[Fillable]` or `#[Hidden]` in this project. Use `protected $fillable` and `protected $hidden` properties.

---

## Authentication & Roles

### Breeze Redirect Loop After Login
- **Encountered:** Role-based redirect setup
- **Symptom:** After login, page redirects in an infinite loop or always goes to `/dashboard` regardless of role
- **Root Cause:** Breeze's `AuthenticatedSessionController` uses `redirect()->intended(route('dashboard'))` by default. Without custom role dispatching, all roles land on the same page.
- **Fix:** Use a single `/dashboard` route with a `DashboardController` that serves role-specific content from one view, rather than separate role-based redirects.
- **Prevention Rule:** Any changes to Breeze's base controllers must be tested for all role types (admin, adviser, student).

---

### `EnsureRole` Middleware Not Registered
- **Encountered:** Middleware setup phase
- **Symptom:** `Route [role:admin] not found` or `500 error` when using `->middleware('role:admin')` in routes
- **Root Cause:** The `EnsureRole` middleware exists but its alias `role` was not registered in `bootstrap/app.php`
- **Fix:** Register in `bootstrap/app.php` under `->withMiddleware()` using `$middleware->alias(['role' => \App\Http\Middleware\EnsureRole::class])`. The middleware accepts variadic roles: `role:admin`, `role:admin,adviser`.
- **Prevention Rule:** After creating any middleware, immediately register its alias before writing route groups that use it.

---

## File Storage

### Local Disk Does Not Support temporaryUrl()
- **Encountered:** File download implementation
- **Symptom:** `RuntimeException: This driver does not support creating temporary URLs` when calling `Storage::disk('local')->temporaryUrl()`
- **Root Cause:** The `local` filesystem driver does not support `temporaryUrl()` — only S3-compatible drivers (like R2) do.
- **Fix:** For local dev, serve files via a controller route with auth middleware: `return Storage::disk('local')->download($path)`. In production, use `Storage::disk('r2')->temporaryUrl($path, now()->addMinutes(30))`.
- **Prevention Rule:** Always check which disk is active before calling `temporaryUrl()`. Use `config('filesystems.default')` or wrap in a conditional.

---

### `Storage::disk()` Returns Narrow `Filesystem` Interface — `temporaryUrl()` and `download()` Invisible to Static Analysis
- **Encountered:** Phase 4 — ResearchService file download implementation (2026-04-09)
- **Symptom:** IDE/static analysis flags `temporaryUrl()` and `download()` as "Undefined method" on the result of `Storage::disk()`. No runtime error, but analysis fails.
- **Root Cause:** `Storage::disk()` is typed to return `Illuminate\Contracts\Filesystem\Filesystem`. This narrow interface does NOT declare `temporaryUrl()` (that's on `Illuminate\Contracts\Filesystem\Cloud`) or `download()` (that's only on the concrete `Illuminate\Filesystem\FilesystemAdapter`). Both methods exist at runtime on the adapter, but static analysis cannot see them through the interface type.
- **Fix:** Extract the disk into a local variable with a `@var FilesystemAdapter` PHPDoc cast, then call methods on it:
  ```php
  use Illuminate\Filesystem\FilesystemAdapter;
  /** @var FilesystemAdapter $disk */
  $disk = Storage::disk($diskName);
  $url = $disk->temporaryUrl(...);       // now resolvable
  $response = $disk->download(...);      // now resolvable
  ```
- **Prevention Rule:** Any time you call `Storage::disk(...)` and access methods beyond basic `put/get/delete/exists`, assign to a local `FilesystemAdapter` variable with a `@var` docblock first.

---

## Frontend / TailwindCSS

### Design Bleed Between Projects — Visual Fingerprint Contamination
- **Encountered:** Phase 5 — Design audit (2026-04-11)
- **Symptom:** SRMS views contained 11+ instances of `uppercase tracking-widest` labels, 11+ `&middot;` dot separators, 12+ `rounded-sm` sharp corners — all patterns copied from BEU-AIS (the developer's own project). Side-by-side comparison showed clear visual similarity.
- **Root Cause:** AI decision tree bootstrap (`docs/ai-decision-tree-bootstrap.md`) was copied from BEU-AIS to SRMS. The bootstrap template carried over design conventions (uppercase labels, dot separators, sharp corners) as "standard" patterns. AI agents then applied these "conventions" to every new component.
- **Fix:** (1) Audited and replaced all 30+ instances across Blade components: `uppercase tracking-*` → normal case, `&middot;` → commas/pipes, `rounded-sm` → `rounded-md`/`rounded-lg`. (2) Added Design Differentiation Lockdown section to CLAUDE.md (§10a) and AGENTS.md (/ui). (3) Added prohibited patterns to the `/avoid` table. (4) Added a design differentiation questionnaire to the bootstrap template.
- **Prevention Rule:** When bootstrapping docs for a new project, ALWAYS include a "Design Differentiation" section that explicitly lists patterns from prior projects that must NOT be used. The bootstrap template now has a mandatory `DESIGN_ANTI_PATTERNS` field. Before writing any UI, run the 5-question checklist from CLAUDE.md §10a.

### Tailwind Classes Not Rendering After Adding New Ones
- **Encountered:** UI development
- **Symptom:** New Tailwind classes appear in the HTML source but the styles are not applied
- **Root Cause:** Vite's build process compiles Tailwind CSS by scanning Blade files for class names. New classes added after the last build are not included in the compiled CSS.
- **Fix:** Run `npm run build` after adding any new Tailwind class to a Blade file.
- **Prevention Rule:** Always run `npm run build` after any Blade or CSS change. Consider running `npm run dev` for hot-reload during active development.

---

## Alpine.js

### Modal Lock — One-Way x-data Bridge
- **Encountered:** Prior build — modal implementations
- **Symptom:** A modal opens correctly the first time. After closing it via the backdrop or X button, clicking the trigger again does nothing. The modal stays permanently closed until the page is hard-refreshed.
- **Root Cause:** Each modal was wrapped in a child `x-data="{ open: parentVar }"` div with `x-effect="open = parentVar"`. This creates a ONE-WAY bridge. When the modal backdrop or X button sets `open = false` (in the child scope only), the parent variable is NOT updated. The next click sets `showXxx = true`, but it was already `true`, so `x-effect` detects no change.
- **Fix:** Add `x-init="$watch('open', v => { if (!v) parentVar = false })"` to every modal wrapper div. This makes the bridge bidirectional.
- **Prevention Rule:** Always use bidirectional state binding between parent and child Alpine.js scopes for modals.

---

*Append new entries below as they are encountered.*

### moveToApproved() — Do Not Move Files on Status Change
- **Encountered:** 2026-04-15 — Review approval workflow
- **Symptom:** `League\Flysystem\UnableToMoveFile` — Unable to copy file from `research/manuscripts/...` to `research/approved/...`
- **Root Cause:** A `moveToApproved()` method was added to `ResearchService` that tried to move PDFs from `research/manuscripts/` to `research/approved/` on approval. The `research/approved/` directory never existed locally, and the `local` disk does not auto-create parent directories on `move()`.
- **Fix:** Removed `moveToApproved()` entirely. Files stay in `research/manuscripts/` for their entire lifecycle. Status transitions update the DB record only — no file system operations.
- **Prevention Rule:** Never move uploaded files on status change. The DB `status` column is the source of truth for approval state. File paths should be write-once (set on upload, never changed). Directory moves add fragile coupling between filesystem state and DB state.

### Table Column Width Expands to Match Longest Content
- **Encountered:** 2026-04-12 — All page-level tables
- **Symptom:** Table columns (especially Title) expand to the full width of the longest content, pushing other columns off-screen or making the table abnormally wide on mobile.
- **Root Cause:** HTML tables default to `table-layout: auto`, which sizes columns based on content. `max-w-*` and `truncate` are treated as suggestions, not constraints. Without `table-fixed`, the browser ignores width hints.
- **Fix:** Added `table-fixed` to `<x-table.wrapper>` globally. Left the Title `<x-table.heading>` with **no explicit width class** — under `table-fixed`, the browser gives all remaining space to columns with no width hint. Secondary columns get explicit pixel widths (`w-20`, `w-24`, `w-28`, `w-44`). Added `truncate` to title text elements inside cells so long text is clipped instead of expanding the column. Added `hidden sm:table-cell` / `hidden md:table-cell` / `hidden lg:table-cell` to secondary columns for mobile responsiveness.
- **Prevention Rule:** Every table MUST use `table-fixed` (set globally via wrapper). The Title heading MUST have **no explicit width** — it absorbs the remainder after fixed-width columns claim their space. Do NOT add `w-2/5` or any percentage/pixel class to the Title `<x-table.heading>`. Do NOT mix percentage (`w-2/5`) with pixel (`w-24`, `w-28`) widths in the same row of headings — under `table-fixed`, this causes the title column to collapse. Do NOT use the `wrap` prop on a title cell that also uses `truncate` on inner text — `wrap` adds `break-words` which conflicts with `white-space: nowrap` from `truncate`. Secondary columns (email, course, adviser, date) use explicit pixel widths and responsive hiding. Date cells use `whitespace-nowrap`.

---

## Role Architecture

### Three-Role System — Admin, Adviser, Student
- **Encountered:** Phase 3 (revised) — Role architecture rework
- **Symptom:** Original 2-role system (`admin`/`student` + `is_admin` boolean) confused the domain model. All staff shared `role=admin`, making it unclear who was an adviser vs system admin. The `is_admin` flag naming was also confusing alongside `role=admin`.
- **Root Cause:** The real domain has three distinct roles: Admin (system manager), Adviser (academic role), Student. An admin can optionally also be an adviser (dual capability). Two enum values couldn't express this properly.
- **Fix:** Changed `role` enum to 3 values: `admin`, `adviser`, `student`. Renamed `is_admin` to `is_adviser` (boolean on admin users granting adviser capabilities). Updated `EnsureRole` middleware: `role:admin` = `role=admin`, `role:adviser` = `role=adviser` OR (`role=admin` + `is_adviser=true`), `role:student` = `role=student`. Dashboard uses 3 match arms. User model has `canAdvise()` instance method and `advisers()` scope.
- **Prevention Rule:** The `role` enum has 3 values. The `is_adviser` boolean is only relevant for `role=admin` users. Use `User::advisers()` scope to query all users who can advise. Use `$user->canAdvise()` to check an individual user.

### Scope vs Instance Method Name Collision
- **Encountered:** Phase 3 (revised) — Role rework
- **Symptom:** `User::canAdvise()->orderBy(...)` threw "Expected type object. Found bool." in static analysis.
- **Root Cause:** The User model had both `canAdvise(): bool` (instance method) and `scopeCanAdvise(Builder): Builder` (query scope). When called statically as `User::canAdvise()`, PHP resolves the instance method first, returning `bool` instead of a Builder.
- **Fix:** Renamed the scope to `scopeAdvisers()` so it's called as `User::advisers()`. The instance method `canAdvise()` remains for per-user checks.
- **Prevention Rule:** Never name a query scope the same as an instance method. If you have `$user->canAdvise()`, the scope must use a different name like `scopeAdvisers()`.

### Email Format — No Dashes, Strip All Special Characters
- **Encountered:** Phase 3 — Registration email generation
- **Symptom:** Email generation was replacing spaces with dashes (`hernandez-nino.marcos@srms.site`) instead of stripping them entirely.
- **Root Cause:** Regex used `/[^a-z0-9\-]/` which preserved dashes. The correct behavior is to strip ALL non-alphanumeric characters.
- **Fix:** Changed regex to `/[^a-z0-9]/` — removes spaces, dashes, apostrophes, and all special characters. Result: `lastname.firstname@srms.site` with no dashes anywhere.
- **Prevention Rule:** Email generation regex must be `/[^a-z0-9]/` (no dash in the character class). Format is always `lastname.firstname@srms.site`.

### Blade @json ParseError With Complex Expression
- **Encountered:** 2026-06-03 — Adviser attendance show page
- **Symptom:** `ParseError: syntax error, unexpected token ";"` when loading adviser attendance detail.
- **Root Cause:** `@json(...)` in Blade received a complex ternary with nested function calls and commas. Blade compiled it into invalid PHP and truncated the expression.
- **Fix:** Precomputed the value in an `@php` block and passed a simple variable into `@json`.
- **Prevention Rule:** Use `@json($variable)` with a precomputed value. Avoid putting complex expressions directly inside `@json(...)`.

---

## Middleware & Performance

### Global `no-store` Cache Header Kills Browser BFCache
- **Encountered:** Phase 5 — PreventBackHistory middleware (2026-04-09)
- **Symptom:** Every page navigation (including login, register, archive) freezes/hangs. Network tab shows all requests stuck in "Pending". Even pages with zero data are slow.
- **Root Cause:** `PreventBackHistory` middleware was added to `$middleware->web(append: [...])` in `bootstrap/app.php`. This appended `Cache-Control: no-store, no-cache, must-revalidate` to ALL web responses globally — including public pages. This kills the browser's Back/Forward cache (BFCache), forcing a full blocking HTTP round-trip on every single navigation.
- **Fix:** Removed from global `web(append: [...])`. Registered as a named alias `'no-back'` in `$middleware->alias([...])`. Applied only to auth-protected route groups in `web.php`: dashboard, profile/notifications, student, adviser, admin.
- **Prevention Rule:** Never add security headers like `no-store` to the global `web` middleware group. Scope them to auth-protected route groups only. Public pages (login, register, archive) need normal browser caching to feel fast. Always test both public and authenticated pages after adding any middleware.

### Database Session + Cache Drivers Slow on WAMP64
- **Encountered:** Phase 5 — Performance debugging (2026-04-09)
- **Symptom:** All pages load slowly even with zero data. Every navigation takes seconds. Network tab shows all document requests as "Pending" while waiting for server response.
- **Root Cause:** `.env` had `SESSION_DRIVER=database` and `CACHE_STORE=database`. Every single request made 3-4 extra MySQL roundtrips: session read, session write, cache read, and potentially cache write. On WAMP64/Windows, each TCP roundtrip to `127.0.0.1:3306` has measurable overhead. Combined with no route/config/view caching, Laravel was reparsing everything on every request.
- **Fix:** Changed to `SESSION_DRIVER=file` and `CACHE_STORE=file` for local dev. Converted closure route `Route::get('/', function() {...})` to `Route::redirect('/', '/login')` to enable route caching. Ran `php artisan config:cache`, `route:cache`, `view:cache`.
- **Prevention Rule:** For local WAMP dev, always use `file` driver for sessions and cache. Save `database` driver for production if needed. Always enable route/config/view caching after development stabilizes. Never use closures in route files — they prevent route caching.

### No External Fonts — Use System Font Stack Only
- **Encountered:** 2026-04-09/10 — Performance debugging phases
- **Symptom (path travelled):** (1) `@import url(...)` in CSS blocked rendering — blank flash before content. (2) Switched to `<link rel="preconnect">` + `<link rel="stylesheet">` in `<head>` — better, but still 3+ DNS lookups and TLS round-trips to `fonts.googleapis.com` per navigation. (3) Downloaded woff2 files + `@font-face` in `app.css` — zero external requests, but added font files to the repo and `font-display: swap` still caused brief glyph substitution.
- **Root Cause:** Any external or custom font involves either blocking network overhead or FOIT/FOUT. For an intranet-style system where every navigation is instant, custom fonts are pure cost with no typographic benefit.
- **Fix:** Removed all Google Fonts `@import`, `<link>` tags, self-hosted woff2 files, and `@font-face` declarations. The entire app uses `font-sans` (Tailwind's system font stack: `system-ui, -apple-system, Segoe UI, Roboto, sans-serif`). No font-related network requests. Navigation is instant.
- **Prevention Rule:** Do NOT add any `@import`, `<link rel="stylesheet">` from Google Fonts, `@font-face` declarations, or woff2 files to this project. The system font stack is final. Hierarchy is achieved through weight (`font-medium`, `font-semibold`, `font-bold`) and size (`text-xs` → `text-2xl`), not typeface variety.

## Authentication & Sessions

### `redirect()->intended()` Causes Cross-Role 403 After Logout/Re-login
- **Encountered:** 2026-04-10 — admin logs out, teacher logs in, gets 403 on admin URL
- **Symptom:** Login as admin, visit `/admin/courses`. Logout. Login as teacher. Teacher is redirected to `/admin/courses` instead of `/dashboard`, hits `role:admin` middleware, gets 403 Unauthorized.
- **Root Cause:** When an unauthenticated user requests a protected URL, Laravel's `Authenticate` middleware saves that URL in `session('url.intended')`. If the admin was on `/admin/courses` and clicked Back after logout, the browser requests `/admin/courses`, auth middleware stores it in the session, and redirects to `/login`. The subsequent login by ANY user calls `redirect()->intended()` which reads that stale URL and redirects to it — regardless of whether the new user has permission.
- **Fix:** Replaced `redirect()->intended(route('dashboard'))` with explicit `$request->session()->forget('url.intended')` followed by `redirect()->route('dashboard')`. Every login now always lands on the role-dispatched dashboard.
- **Prevention Rule:** Never use `redirect()->intended()` in multi-role apps. Each role has different allowed routes. Always redirect to the role-dispatched dashboard after login. If a targeted post-login redirect is ever needed, validate the URL against the user's role before following it.

---

## Component Architecture

### Duplicated Paper View Panel Across 3 Modals
- **Encountered:** 2026-04-10 — Component consolidation audit
- **Symptom:** The paper detail view panel (status grid, abstract, authors, defense schedule, review history, download button) was copy-pasted across `admin/paper-actions-modal`, `student/research-actions-modal`, and `adviser/review-actions-modal` with ~60-80 identical lines in each file. Minor inconsistencies crept in (e.g., student modal displayed `start_time` as a raw string while adviser modal called `->format('g:i A')`).
- **Root Cause:** Each modal was built independently without extracting the shared view panel into a component.
- **Fix:** Created `<x-ui.paper-view>` component (`components/ui/paper-view.blade.php`) with props `paper`, `downloadRoute`, and `showSubmitter` (default true). All three modals now render `<x-ui.paper-view :paper="$paper" :downloadRoute="..." />` instead of inline markup.
- **Prevention Rule:** Before building any modal view panel, check if another modal already renders the same data. If two or more modals display the same entity, extract the shared view into a `<x-ui.*>` component. The modal keeps only its unique form panels.

### Inline Modal Footer Buttons Repeated 10+ Times
- **Encountered:** 2026-04-10 — Component consolidation audit
- **Symptom:** The pattern `<div class="px-6 pb-5 pt-4 flex justify-end gap-3"><x-ui.button Cancel /><x-ui.button Submit /></div>` was hand-written in every modal form panel.
- **Root Cause:** No shared footer component existed. Each modal duplicated the cancel + submit button bar.
- **Fix:** Created `<x-ui.modal-footer>` component with props `modalName`, `submitLabel`, and `submitVariant`. All modal form panels now use `<x-ui.modal-footer :modalName="$modalName" submitLabel="Save" />`.
- **Prevention Rule:** Every modal form must use `<x-ui.modal-footer>` for its cancel/submit buttons. Never inline the button bar.

### Separate Create and Actions Modals for Same Entity
- **Encountered:** 2026-04-10 — Component consolidation audit
- **Symptom:** Courses had `course-create-modal.blade.php` AND `course-actions-modal.blade.php`. Announcements had the same pattern. Both files contained structurally identical form fields (code, name for courses; title, message, course_id for announcements).
- **Root Cause:** Create and view/edit modals were built as separate files even though they share the same entity and fields.
- **Fix:** Merged each pair into a single component: `<x-admin.course-modal>` and `<x-admin.announcement-modal>`. When called without props (`:course="null"`), the component renders a plain create form. When called with a record (`:course="$course"`), it renders the tabbed view/edit/delete shell.
- **Prevention Rule:** Never create separate `{entity}-create-modal` and `{entity}-actions-modal` files. Use a single `{entity}-modal` component with an `$isCreate` flag. The index page calls it once without the record (for create) and once per record (for actions).

### Detail Field Label Pattern Duplicated Dozens of Times
- **Encountered:** 2026-04-10 — Component consolidation audit
- **Symptom:** The inline pattern `<div><span class="text-xs text-gray-400 font-medium">Label</span><p>Value</p></div>` was repeated in every modal's view panel.
- **Fix:** Created `<x-ui.detail-field label="...">` component. View panels now use `<x-ui.detail-field label="Status"><x-ui.badge ... /></x-ui.detail-field>`.
- **Prevention Rule:** Any label + value display in a modal view panel must use `<x-ui.detail-field>`. Never inline the label span.

### Defense start_time Type Inconsistency Across Modals
- **Encountered:** 2026-04-10 — Component consolidation into `<x-ui.paper-view>`
- **Symptom:** Student modal rendered `$paper->defenseSchedule->start_time` as a raw string (no format call). Adviser modal called `->format('g:i A')` on it. After merging into a shared `paper-view` component, calling `->format()` on a string crashes.
- **Root Cause:** `DefenseSchedule::$casts` may or may not cast `start_time` to a Carbon instance depending on the migration/model state. Different modals handled it differently and the inconsistency was invisible until shared.
- **Fix:** Added an `instanceof \Carbon\Carbon` guard in `paper-view.blade.php`. Safe across both typed and untyped states: `$paper->defenseSchedule->start_time instanceof \Carbon\Carbon ? $paper->defenseSchedule->start_time->format('g:i A') : $paper->defenseSchedule->start_time`.
- **Prevention Rule:** Never assume a time/date field `instanceof Carbon` in a shared component. Always guard before calling `->format()`. Prefer adding explicit `$casts` to models rather than relying on implicit type coercion.

---

## Database Records & Relationships

### Deleting User Records Orphans `belongsTo(User)` FK References
- **Encountered:** 2026-04-11 — Deleting old test accounts (`user.admin@srms.site`, `user.teacher@srms.site`, `user.student@srms.site`) orphaned `defense_schedules.created_by` FK
- **Symptom:** `ErrorException: Attempt to read property "first_name" on null` at `DashboardController.php:243` and `ScheduleController.php:42` — both accessing `$s->creator->first_name` directly.
- **Root Cause:** Hard-deleting a `User` record whose `id` is referenced in another table as a FK (e.g. `defense_schedules.created_by`) leaves that FK pointing at a non-existent row. Eloquent's `belongsTo(User::class, 'created_by')` then returns `null`. Any code accessing `->property` directly on that null crashes.
- **Fix:** Wrapped both accesses in a null-safe ternary: `$s->creator ? $s->creator->first_name . ' ' . $s->creator->last_name : 'Unknown'`. Applied in both `DashboardController::getScheduleData()` and `ScheduleController::events()`.
- **Prevention Rule (for the code):** Every property access on a `belongsTo` relationship MUST assume the related record could be deleted. Use null-safe operator (`$s->creator?->first_name`) or an explicit ternary (`$s->creator ? ... : 'Unknown'`). Never chain `->property` directly on a relationship result.
- **Prevention Rule (before deleting DB records):** Before hard-deleting ANY user or other FK-referenceable record, run these checks:
  1. Search all models for `belongsTo(User::class` — find every FK pointing at the user.
  2. Check all controller/service code that accesses properties off those relationships.
  3. Either guard the property access with `?->` OR add `onDelete('set null')` to the FK migration and make the column nullable.
  4. Run `grep_search` for the record's FK column name (`created_by`, `adviser_id`, `submitted_by`, `reviewer_id`, `user_id`) before deleting.
- **Models with `belongsTo(User)` relationships in SRMS (as of 2026-04-11):**
  - `ResearchPaper` — `submitted_by` → `User` (submitter), `adviser_id` → `User` (adviser)
  - `Review` — `reviewer_id` → `User`
  - `DefenseSchedule` — `created_by` → `User` (creator), `adviser_id` → `User`
  - `Announcement` — `created_by` → `User`
  - Any new model added later must be added to this list.

---

### Alpine x-data Broken By Single-Quoted @js Attribute
- **Encountered:** 2026-06-03 - PDF annotation viewer debug
- **Symptom:** The annotate page shell loaded, but the document area stayed blank and the pager rendered as `Page of` with no numbers.
- **Root Cause:** The Blade component used `x-data='annotationViewer(@js($viewerConfig))'`. Laravel's `@js` output contains single quotes, so the browser broke the HTML attribute before Alpine could initialize the component.
- **Fix:** Use double quotes around the Alpine attribute: `x-data="annotationViewer(@js($viewerConfig))"`.
- **Prevention Rule:** When embedding `@js(...)` or `Js::from(...)` inside Alpine attributes, wrap the HTML attribute in double quotes or precompute the payload into a safe attribute.

---

### Annotation JSON Failure Must Not Hide Loaded PDF
- **Encountered:** 2026-06-03 - PDF annotation viewer debug
- **Symptom:** The annotate page showed `Page 1 of 14`, then displayed `The document could not be loaded.`
- **Root Cause:** The viewer opened the PDF successfully, then loaded annotation JSON before rendering the first page. A failure in the annotations request was caught by the same broad document-load handler, so a secondary annotation failure hid a valid PDF.
- **Fix:** Load and render the PDF independently. Annotation loading now has its own error flag and warning, and PDF page rendering has its own error state.
- **Prevention Rule:** In document viewers, treat the source document as the primary path. Optional overlays, comments, or metadata should fail separately and must not block document rendering.

---

### Alpine init() Plus x-init Causes Duplicate PDF.js Render
- **Encountered:** 2026-06-03 - PDF annotation viewer debug
- **Symptom:** The viewer showed `Page 1 of 14`, then `This page could not be rendered.`
- **Root Cause:** Alpine automatically calls a component method named `init()`. The Blade also had `x-init="init()"`, so two PDF.js render tasks started against the same canvas. PDF.js rejects concurrent rendering into one canvas.
- **Fix:** Removed the manual `x-init` call, added an initialization guard, and serialized/cancelled render tasks before starting another page render.
- **Prevention Rule:** Do not call Alpine component `init()` manually with `x-init`. Use one initialization path only, and guard PDF.js canvas rendering with a single active render task.

---

### Table Edit Form Must Be A Sibling Row
- **Encountered:** 2026-06-03 - Adviser attendance timeline edit UI
- **Symptom:** Clicking the attendance row edit icon expanded a large blank-looking area under the table row, but the edit inputs were not visible where expected.
- **Root Cause:** The edit `<td colspan="6">` was placed inside the same `<tr>` after the six display cells. Browsers repaired the invalid table structure by treating the edit panel as extra cells in the current row, so the row grew while the form rendered in the wrong table position.
- **Fix:** Render each edit panel as its own sibling `<tr>` immediately after the display row, with one `<td colspan="6">` inside it. Control the open row from table-level Alpine state (`editingRow`) and keep the form's field state in the edit row's own `x-data`.
- **Prevention Rule:** In Blade tables, never put a second-row edit/detail panel as an extra `<td>` inside an existing data row. For expandable table content, close the display `<tr>` first, then render a sibling `<tr x-show="...">` with the colspan cell.

---

### Seeder That References a Specific Email Will Silently Bail If That Account Is Deleted
- **Encountered:** 2026-04-12 — DefenseScheduleSeeder used `user.admin@srms.site` as its admin lookup. That old test account was deleted in the previous session. The seeder's `if (! $admin) { return; }` guard silently exited, leaving existing seeded schedules with orphaned `created_by` FK. Calendar showed "Unknown" for all seeded schedules.
- **Symptom:** All seeded defense schedules show "Unknown" in the creator popover. Re-running `db:seed` does nothing (seeder returns early). New schedules created by real users work fine.
- **Root Cause:** Seeder looked up a test email (`user.admin@srms.site`) that no longer exists after the cleanup of old duplicate accounts. The seeder's silent `return` means it never ran `updateOrCreate` to fix the orphaned `created_by` values.
- **Fix:** Changed seeder to look up `santos.ricardo@srms.site` (the real admin from `AdminSeeder`). Re-ran `php artisan db:seed --class=DefenseScheduleSeeder` — `updateOrCreate` updated `created_by` on all 3 existing records to the valid admin ID.
- **Prevention Rule:** Seeders that depend on specific user accounts MUST reference the email from the authoritative seeder (`AdminSeeder`, `AdviserSeeder`, etc.). Never hard-code old test emails. When test accounts are deleted, run `grep_search` for any seeder that looked up those emails. Also: every seeder guard should use `$this->command->warn(...)` instead of silent `return` so failures are visible in CI and `db:seed` output.
- **CLAUDE.md note:** The credentials table in Section 9 must always match the actual emails in `AdminSeeder.php`. If they diverge, agents will use the wrong email and seeders/scripts will silently fail.

---

### Calendar `overflow-hidden` on Parent Clips the Child Scroll Container
- **Encountered:** 2026-04-12 — Defense calendar cut off on small laptop screens with no horizontal scrollbar
- **Symptom:** On laptop screens, the calendar appeared cropped (only showing 1–2 day columns). No horizontal scrollbar was visible even though `overflow-x-auto` was on the inner wrapper.
- **Root Cause:** The outer card wrapper had `overflow-hidden` (Tailwind pattern used to clip rounded corners). This competed with the inner `overflow-x-auto` scroll container. Combined with `min-width: 560px` on `.srms-calendar`, the calendar was forced wider than its container but the scrollbar was unreachable. Additionally, `min-width: 560px` was a global floor that prevented FullCalendar from responsively shrinking below 560px.
- **Fix:** (1) Removed `overflow-hidden` from the outer card div — the `rounded-lg` border-radius still applies to the element itself and is visually fine without clipping children. (2) Removed `min-width: 560px` from `.srms-calendar`. (3) Added `min-width: 56px` to both `.fc-col-header-cell` and `.fc-daygrid-day` — 7 columns × 56px = 392px total minimum. Calendar now shrinks responsively to fit any container, with a readable per-cell floor.
- **Prevention Rule:** Never put `overflow-hidden` on a parent element that contains a horizontally scrollable child. Use `overflow-hidden` only on elements that do NOT contain scroll containers as descendants. For calendar or table components that need responsive horizontal scroll, set min-width at the CELL level (e.g. `min-width: 56px`) rather than a global `min-width` on the container.
  - Any new model added later must be added to this list.
