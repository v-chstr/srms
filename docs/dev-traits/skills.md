# skills.md - AI Capability And Constraint Catalogue

Use this as a quick boundary list for AI-assisted work on SRMS.

## Project Skills

| Area | Guidance |
|---|---|
| Laravel | Use Laravel 13 patterns, Eloquent, Blade, middleware, route groups, and Artisan. |
| Blade | Use Blade components and Alpine.js. Do not introduce React, Inertia, or Vue. |
| Tailwind | Use project tokens: `primary-*`, `accent-*`, and `status-*`. |
| Auth | Use Breeze foundation with custom SRMS views and role-aware dashboard behavior. |
| Roles | `admin`, `adviser`, `student`; admin can advise only with `is_adviser=true`. |
| Storage | Use `Storage::disk()` for local/R2 compatibility. |
| PDF | Use `barryvdh/laravel-dompdf`; keep PDF templates simple. |
| Deployment | Laravel Cloud; incremental migrations only. |

## What AI Should Not Do

- Add React, Vue, Inertia, repositories, DTOs, action classes, or helper layers unless explicitly needed and consistent with the repo.
- Add services for simple CRUD pass-through.
- Use `$request->all()` for persistence.
- Use PHP attribute fillable/hidden syntax.
- Use raw status colors instead of semantic tokens or `<x-ui.badge>`.
- Use `temporaryUrl()` on the local disk.
- Squash migrations.
- Run destructive DB commands without confirmation.
- Add global no-store/security middleware to the entire `web` group.
- Use `database` session/cache drivers for local WAMP development unless the user asks.
- Add external fonts or CSS imports.
- Use route closures where route caching matters.

## Generation Defaults

- Models: `$fillable`, casts, relationships, and scopes when useful.
- Controllers: thin coordination only.
- Services: only for real business workflows or reused logic.
- Validation: inline for small one-off forms; FormRequest for reused or larger rules.
- Views: use existing components first; extract components only for repeated patterns.
- Seeders: idempotent with `updateOrCreate()` or equivalent.
- Queries: eager-load relationships shown in loops.

## Common Patterns

```php
ResearchPaper::approved()->where('course_id', $id)->get();

Course::updateOrCreate(
    ['code' => 'IT'],
    ['name' => 'Information Technology']
);

abort(403, 'Unauthorized.');

$path = $file->store('research/manuscripts', config('filesystems.default'));

$papers = ResearchPaper::with(['course', 'authors', 'adviser'])
    ->latest()
    ->paginate(15);
```
