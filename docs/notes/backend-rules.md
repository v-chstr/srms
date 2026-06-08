# Backend Rules

Use this when editing controllers, services, models, queries, requests, or middleware.

## Controllers

Controllers should coordinate:

1. Validate and authorize.
2. Query models or call a service when needed.
3. Return a view, redirect, download, or JSON response.

Keep simple CRUD simple. Do not create a service for a one-line create/update unless the logic is shared, domain-heavy, or already belongs to an existing service.

## Services

Use services for:

- Research submission/revision workflows.
- File storage workflows.
- Citation generation.
- Multi-step domain behavior reused in more than one controller.
- Logic that would make a controller hard to read or test.

Do not use services for:

- Plain `Model::create($validated)`.
- One-off query assembly that belongs in a controller or model scope.
- Formatting that belongs in Blade/components.

## Validation

- Never pass `$request->all()` to `create()` or `update()`.
- Use `$request->validate([...])` for small one-off validations.
- Use FormRequest when validation is reused, long, or has meaningful authorization logic.
- Persist only validated data plus explicitly assigned server-side fields.

## Queries

- Eager-load relationships used in collection views.
- Use scopes for repeated filters, especially statuses and ownership rules.
- Apply filtering server-side. Do not send full collections to Blade just for JavaScript filtering.
- Avoid raw DB queries unless Eloquent cannot express the query cleanly.

Example:

```php
$papers = ResearchPaper::with(['course', 'authors', 'adviser'])
    ->latest()
    ->paginate(20);
```

## Authorization

- Put role protection on route groups with `EnsureRole`.
- Use `abort(403)` for unauthorized access.
- Do not rely on hiding buttons in Blade as the only protection.
- If a user can access only their own records, enforce that in the query or authorization check.

## Models

- Use `protected $fillable` and `protected $hidden`.
- Define relationships clearly.
- Add casts for dates, booleans, enums/status-like fields when useful.
- Do not name a scope the same as an instance method.

## File Storage

- Use `Storage::disk()` and Laravel uploaded file APIs.
- Store relative paths in the database.
- Do not move uploaded files on status changes unless the user explicitly asks and the storage impact is confirmed.
- Local disk does not support `temporaryUrl()`. Use controller downloads locally.

## After Changes

- Run focused tests when available.
- Run `php artisan route:clear` after route changes.
- Run `php artisan view:clear` after layout/view changes.
