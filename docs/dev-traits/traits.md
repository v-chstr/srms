# traits.md - SRMS Coding Conventions

Use this file for current naming, placement, and structure rules. Keep changes simple and consistent with the code already in the repo.

## Core Principle

Search before creating. Reuse before extracting. Extract only when repetition or complexity makes it worth it.

For Blade, this means:

1. Check `resources/views/components/`.
2. Use an existing component when it fits.
3. Create a component when the same UI pattern appears more than once.
4. Leave a one-off layout fragment inline when a component would add noise.

## Naming

| Thing | Convention | Example |
|---|---|---|
| Controllers | PascalCase with suffix | `ResearchController` |
| Role controllers | Role namespace | `App\Http\Controllers\Admin\UserController` |
| Models | Singular PascalCase | `ResearchPaper` |
| Services | PascalCase with suffix | `ResearchService` |
| Migrations | Timestamped snake_case | `create_research_papers_table` |
| Seeders | PascalCase with suffix | `CourseSeeder` |
| Routes | Dot notation | `student.research.index` |
| Blade views | Foldered snake_case | `pages/student/research/index.blade.php` |
| Blade components | Kebab file, dot tag | `components/ui/button.blade.php` as `<x-ui.button>` |
| PHP variables | camelCase | `$researchPapers` |
| DB tables | Plural snake_case | `research_papers` |
| DB columns | snake_case | `submitted_by`, `course_id` |

## Blade Components

Common namespaces:

| Namespace | Folder | Use |
|---|---|---|
| `<x-ui.*>` | `components/ui/` | Generic UI and shared display patterns |
| `<x-form.*>` | `components/form/` | Inputs, labels, errors |
| `<x-table.*>` | `components/table/` | Table scaffolding |
| `<x-layouts.*>` | `components/layouts/` | Layout shells and page helpers |
| Domain folders | `components/admin`, `student`, `adviser`, etc. | Role/domain-specific UI |

Component rules:

- Declare props with `@props([...])` and safe defaults.
- Use slots for flexible content.
- Keep Tailwind classes in Blade/components, not controllers.
- Put conditional class logic in the component when it is reused.
- Status colors belong in `<x-ui.badge>`.
- Every `match()` in a component must include a `default` arm.

## Backend

- Controllers validate and coordinate. They should not contain business workflows.
- Services are for business logic that is shared, complex, or already part of an existing service area.
- Simple CRUD validation can stay inline in the controller.
- Use FormRequest classes when validation is reused, long, or authorization-specific.
- Use Eloquent relationships and scopes instead of repeated raw query chains.
- Eager-load relationships that are shown in collection views.
- Use route middleware groups for auth and roles.

## Routes

- Student routes: `/student`, route names `student.*`, middleware `auth`, `role:student`.
- Adviser routes: `/adviser`, route names `adviser.*`, middleware `auth`, `role:adviser`.
- Admin routes: `/admin`, route names `admin.*`, middleware `auth`, `role:admin`.
- Shared authenticated routes use `auth` and a specific capability/role check when needed.
- Public archive routes may stay public when they only expose approved papers.
- Prefer the route pattern already used in `routes/web.php`.

## Database And Models

- Keep `Schema::defaultStringLength(191)` in `AppServiceProvider::boot()`.
- Each schema change gets a new migration.
- Do not squash migrations for Laravel Cloud.
- Use `foreignId()->constrained()` where possible.
- Make delete behavior explicit with cascade, restrict, or null behavior.
- Seeders use `updateOrCreate()` or another idempotent pattern.
- Use `protected $fillable` and `protected $hidden`, not PHP attribute syntax.

## Frontend Style

- TailwindCSS only. No inline `style=""` attributes or custom CSS files unless the existing feature already requires one.
- No default Breeze visual styling.
- Use `primary-*`, `accent-*`, and `status-*` tokens from `tailwind.config.js`.
- Use the existing radius and spacing patterns from components, usually `rounded-md`.
- No `uppercase`, `tracking-*`, `rounded-sm`, dot separators, emoji, or em dashes in UI copy.
- Flash messages use `<x-ui.alert>`.
- Repeated status output uses `<x-ui.badge>`.
- Form errors use existing form components or `@error`.

## JavaScript

- This is a server-rendered Blade app. Keep JavaScript small.
- Use Alpine.js for local UI state such as modals, tabs, filters, and toggles.
- Reusable Alpine components can live in `resources/js/app.js`.
- Page-specific scripts should be pushed from the Blade view when needed.
- Do not create JS `utils`, `hooks`, or framework-style folders.
- No `console.log` in production code.

## Security

- Every form has `@csrf`.
- Validate before mass assignment.
- File downloads stay behind controller routes and authorization checks.
- Role checks belong in middleware or explicit authorization, not hidden UI alone.
- Multi-role login should land on the role-dispatched dashboard, not stale intended URLs.
