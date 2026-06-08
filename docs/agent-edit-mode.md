# Agent: Edit / Build Mode

Use this guide for concrete implementation tasks: bug fixes, new features, controller updates, Blade views, migrations, UI changes, and focused refactors.

## Before Editing

1. Read `docs/dev-traits/learn.md`.
2. Read `docs/dev-traits/traits.md`.
3. Search the codebase for existing files and components.
4. Choose the smallest change that solves the task.

Do not add architecture by default. Add a service, FormRequest, component, migration, or route only when the task actually needs it.

## Decision Guide

### Backend Change

- Keep controllers thin: validate, authorize, call existing model/service logic when needed, return response.
- Use a service only for business logic that is shared, complex, or already belongs to an existing service.
- Use inline validation for simple one-off forms. Use FormRequest for reused or large validation rules.
- Use model scopes for repeated query filters.
- Eager-load relationships used by the view.

### Blade Or UI Change

- Check `resources/views/components/` before writing markup.
- Use existing components for buttons, alerts, badges, form fields, tables, modals, cards, dates, detail fields, and empty states.
- Extract a component when markup repeats in two or more places.
- Keep one-off layout markup inline when extraction would add more complexity than value.
- Follow SRMS design rules: no `uppercase`, `tracking-*`, `rounded-sm`, dot separators, emoji, or em dashes in UI copy.

### Route Change

- Put protected routes in the correct middleware group.
- Use `role:admin`, `role:adviser`, or `role:student` as needed.
- After route changes, run:

```powershell
php artisan route:clear
php artisan view:clear
```

### Migration Or Seeder

- Read `docs/notes/migration-standards.md` first.
- Never squash migrations.
- Never run destructive DB commands without user confirmation.
- Seeders must be idempotent.

### File Upload Or Download

- Read `docs/notes/storage-rules.md`.
- Validate MIME type and size.
- Use `Storage::disk()`.
- Store relative paths in the database.
- Keep downloads behind controller routes and auth/authorization checks.

## Verification

Run only the checks that match what changed:

- PHP syntax or backend behavior: targeted PHPUnit/Pest test if available, or `php artisan test` when practical.
- Blade/Tailwind changes with new classes: `npm run build`.
- Route/layout changes: `php artisan route:clear` and `php artisan view:clear`.
- Migrations: `php artisan migrate` only when the user expects schema changes and the migration is non-destructive.

End with the report format in `AGENTS.md`.
