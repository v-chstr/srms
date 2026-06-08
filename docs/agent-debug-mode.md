# Agent: Debug Mode

> Use this guide when: "It's broken", "I got a 500", "it stopped working", "revert that", unexpected behavior.
> **Stop. Do not touch code. Read this first.**

---

## When to enter Debug Mode

- User says "it's broken" or "I'm getting an error"
- A page returns 500, 403, 404, or 419
- Data is missing or wrong
- A file upload/download fails
- A migration failed on deployment
- Something worked before and now doesn't

---

## Step 1: Stop. Do Not Hack.

Read the error message in full before writing a single character of code.
A rushed fix creates a second bug on top of the first.

---

## Step 2: Write a Root Cause Report First

Before proposing any fix, state all three:

1. **What broke** — exact symptom (what the user sees / what the error says)
2. **Why it broke** — the underlying cause
3. **The fix** — what file changes and why that resolves the root cause

Do not write the fix first and reverse-engineer the explanation.

---

## Step 3: Check Laravel Logs

```bash
# View the last 80 lines of the Laravel log
Get-Content storage\logs\laravel.log -Tail 80
```

---

## Step 4: Error Pattern Reference

### HTTP Errors

| Symptom | Likely cause | Where to look |
|---|---|---|
| `500 Internal Server Error` | Unhandled PHP exception | `storage/logs/laravel.log` |
| `403 Forbidden` | `EnsureRole` middleware blocking, or `abort(403)` | `app/Http/Middleware/EnsureRole.php`, route middleware in `web.php` |
| `404 Not Found` | Route not defined, or wrong URL | `php artisan route:list` |
| `419 Page Expired` | `@csrf` missing from a form | The Blade view's `<form>` tag |
| Redirect loop | Role-based redirect sending user in circles | `AuthenticatedSessionController`, route middleware groups |

### PHP / Laravel Exceptions

| Exception | Likely cause | Fix |
|---|---|---|
| `Attempt to read property on null` | Missing `->with()` eager load — relationship not loaded | Add `->with(['course', 'authors'])` to the query |
| `Class not found` | Wrong namespace or missing import | Check `use` statement; run `composer dump-autoload` |
| `SQLSTATE[42S02]: Table not found` | Migration not run | `php artisan migrate` |
| `SQLSTATE[42S22]: Column not found` | New column added in migration but not yet run | `php artisan migrate` |
| `SQLSTATE[23000]: Integrity constraint` | FK violation — inserting a record referencing a non-existent parent | Verify parent record exists; check `constrained()` FK |
| `SQLSTATE[HY000]: 1215 Cannot add foreign key` | Migration order wrong — child table migrating before parent | Fix timestamp order |
| `UnhandledMatchError` | `match()` with no default, given unexpected value | Add `default` arm to the `match()` expression |
| `View not found` | Blade file path does not match `view('...')` call | Check dots vs slashes; verify the file exists |
| `Route not found` | Named route doesn't exist | `php artisan route:list \| findstr "route.name"` |
| `RuntimeException: This driver does not support creating temporary URLs` | Calling `temporaryUrl()` on `local` disk | Use controller download route for local dev; `temporaryUrl()` only works with S3/R2 |

### Deployment-Specific Errors (Laravel Cloud)

| Symptom | Cause | Fix |
|---|---|---|
| `Class not found` on deploy | Class is in a `require-dev` package | Move to `require` package |
| Migration fails on deploy | FK constraint order wrong, or `Schema::defaultStringLength(191)` missing | Read `docs/notes/migration-standards.md` fully |
| `SQLSTATE[42000]: Specified key was too long` | `Schema::defaultStringLength(191)` missing from `AppServiceProvider::boot()` | Add it |
| `No application encryption key` | `APP_KEY` not set in Laravel Cloud environment | `cloud env:set APP_KEY=...` |
| Assets 404 / Tailwind not loading | `npm run build` not run, or `public/build/` not committed | Run `npm run build` |

### Blade / TailwindCSS Errors

| Symptom | Cause | Fix |
|---|---|---|
| Tailwind classes not applying | New class added but `npm run build` not re-run | `npm run build` |
| Component not rendering | File path doesn't match `<x-namespace.name>` tag | Dots map to slashes: `<x-ui.badge>` → `components/ui/badge.blade.php` |
| Props not reaching component | `@props([])` not declared, or wrong prop name passed | Check `@props` declaration at top of component file |
| `old()` value not showing after error | Input missing `value="{{ old('field') }}"` | Add `value="{{ old($name) }}"` to every input |

### File Storage Errors

| Symptom | Cause | Fix |
|---|---|---|
| `This driver does not support creating temporary URLs` | Using `temporaryUrl()` on `local` disk | Use controller download with `Storage::disk('local')->download()` for local dev |
| Upload succeeds but file not in R2 | `FILESYSTEM_DISK` env still set to `local` | Check `.env` — change to `r2` for production |
| Download returns 404 | File path in DB doesn't match actual storage path | Check `file_path` column — should be relative path like `research/manuscripts/xxx.pdf` |

---

## Step 5: Blade-Specific Debugging

**Check what the controller is actually passing to the view:**
```php
// Temporarily in the controller, before return view():
dd(compact('papers', 'courses'));
```

**Check component props at runtime:**
```blade
{{-- Temporarily at the top of any component --}}
@dump($attributes->getAttributes())
```

---

## Step 6: Fix, Then Verify

After applying the fix:
1. If any Blade or asset file changed → `npm run build`
2. If any route or layout changed → `php artisan route:clear && php artisan view:clear`
3. If a migration was added → `php artisan migrate`
4. Hard-reload the browser (Ctrl+Shift+R) to clear cached assets.
5. Re-test the exact scenario that was broken.

---

*After fixing, produce the session report from `CLAUDE.md § 5`. Include what broke, why, and how it was fixed so it's captured in `dev-traits/learn.md`.*
