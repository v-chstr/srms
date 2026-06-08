# AGENTS.md - SRMS Agent Guide

Laravel 13 + Blade + TailwindCSS + Alpine.js student research management system.

Last updated: 2026-06-03

## Start Here

Use the smallest change that solves the request. Do not add layers, services, abstractions, routes, or components unless the current task needs them.

Before editing code:

1. Read `docs/dev-traits/learn.md` for past traps.
2. Read `docs/dev-traits/traits.md` for current conventions.
3. Search the existing code before creating anything new.
4. If touching a specialized area, read only the matching note:
   - Backend/controller/query: `docs/notes/backend-rules.md`
   - Blade/component/UI: `docs/notes/ui-components.md`
   - Migration/seeder: `docs/notes/migration-standards.md`
   - Upload/download/storage: `docs/notes/storage-rules.md`

For planning or debugging:

- Planning/schema/architecture: read `docs/agent-planning-mode.md`, present trade-offs, confirm before structural changes.
- Bug/regression/500/error: read `docs/agent-debug-mode.md`, diagnose the root cause before editing.

## Project Facts

- Backend: Laravel 13, Eloquent ORM, MVC.
- Frontend: Blade, TailwindCSS, Alpine.js. No React, Inertia, or Vue.
- Auth: Breeze Blade stack, custom SRMS views.
- Roles: `users.role` enum with `admin`, `adviser`, `student`.
- Adviser access: `role=adviser` or `role=admin` with `is_adviser=true`.
- Middleware: `role:admin`, `role:adviser`, `role:student` use `EnsureRole`.
- Database: MySQL `srms-db`; keep `Schema::defaultStringLength(191)` in `AppServiceProvider`.
- Storage: use `Storage::disk()`. Local uses `local`; production uses Cloudflare R2/S3.
- Deployment: Laravel Cloud. Never squash migrations.

## Coding Rules

- Keep controllers lean: validate, authorize, call a model/service when needed, return a view/redirect.
- Put business logic in `app/Services/` only when it is real business logic, shared, or complex enough to justify it.
- Prefer model scopes for repeated query rules such as approved/pending/status filters.
- Eager-load relationships used in collections. Do not create N+1 queries in Blade loops.
- Validate before persistence. Never pass `$request->all()` to `create()` or `update()`.
- Seeders must be idempotent with `updateOrCreate()` or equivalent lookup/update logic.
- Use `protected $fillable` and `protected $hidden`; do not use PHP attribute fillable/hidden syntax.
- Use route middleware for role protection. Role violations should `abort(403)`.
- After route or layout changes, run `php artisan route:clear` and `php artisan view:clear`.
- Never run destructive DB commands such as `migrate:fresh`, `drop`, or truncation without user confirmation.

## Blade And UI Rules

The main frontend rule: do not repeat yourself.

- Before writing a card, table, alert, form field, button, badge, modal, or repeated detail row, check `resources/views/components/`.
- Use an existing component when one exists.
- Extract a component when the same markup pattern is used more than once.
- Do not create a component for a one-off layout fragment unless it simplifies the view.
- Keep Tailwind classes in Blade/components, not controllers.
- Flash messages use `<x-ui.alert>` with `session('success')` or `session('error')`.
- Status badges use `<x-ui.badge>`.
- Form fields should prefer `<x-form.*>` components when available.

Component namespaces:

| Namespace | Folder | Use |
|---|---|---|
| `<x-ui.*>` | `resources/views/components/ui/` | Generic primitives and shared display patterns |
| `<x-form.*>` | `resources/views/components/form/` | Inputs with label/error handling |
| `<x-table.*>` | `resources/views/components/table/` | Table wrappers, headings, and cells |
| `<x-layouts.*>` | `resources/views/components/layouts/` | Layout shells and page-level helpers |
| Role/domain folders | `components/admin`, `student`, `adviser`, etc. | Role-specific or domain-specific UI |

## Design Rules

SRMS should feel like a clean academic research portal, not default Breeze and not a generic admin template.

- Use the semantic palette from `tailwind.config.js`: `primary-*`, `accent-*`, and `status-*`.
- Do not use raw status colors inline when a semantic token/component exists.
- Use white/off-white surfaces with subtle borders and restrained shadows.
- No hover lift, scale transforms, decorative blobs, or noisy animation.
- No emoji in UI copy unless the user asks.
- No em dashes in UI copy. Use `N/A`, `None`, `Not assigned`, commas, pipes, or line breaks.
- No `uppercase` or `tracking-*` text styles.
- No `&middot;` or dot separators.
- No `rounded-sm`. Prefer the radius already used by the relevant component, usually `rounded-md`.

## Architecture Restraint

Avoid overengineering:

- Do not introduce a new service for simple one-method CRUD pass-through.
- Do not create FormRequest classes unless validation is reused or large enough to deserve a named class.
- Do not add repositories, DTOs, action classes, helpers, or JavaScript utility folders unless the repo already uses that pattern for the same problem.
- Do not build standalone pages for modal-based CRUD unless existing routes already require them or the user asks.
- Do not refactor unrelated code while fixing a focused issue.

## End Report

After completing a task, include:

```text
CHANGES MADE
  [file path]: [what changed] - [why it was needed]

LEFT UNCHANGED
  [what] - [why not changed]

CONFLICTS OR INACCURACIES FOUND
  [doc/code] states [X] but [reality is Y]

FUTURE DEV NOTES
  [implications of today's work]
```
