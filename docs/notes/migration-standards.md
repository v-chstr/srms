# Migration Standards

> Read this BEFORE writing, editing, or deploying any migration.
> Migration failures are the #1 cause of deployment errors in this project.

---

## Critical: AppServiceProvider Requirement

**This MUST be set. Without it, every migration fails on MySQL with long index errors.**

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Schema;

public function boot(): void
{
    Schema::defaultStringLength(191);
}
```

The error you see without this:
```
SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long; max key length is 767 bytes
```

This affects any indexed `string` column. Required on WAMP64 (local dev) AND on Laravel Cloud (production MySQL).

---

## Rule 1: Always Wrap in Foreign Key Safety Guards

```php
public function up(): void
{
    Schema::disableForeignKeyConstraints();

    // ... table creation

    Schema::enableForeignKeyConstraints();
}

public function down(): void
{
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('research_papers');
    Schema::enableForeignKeyConstraints();
}
```

This prevents rollback failures caused by FK constraints blocking `DROP TABLE`.

---

## Rule 2: Use `foreignId()->constrained()` — Never `unsignedBigInteger`

```php
// CORRECT:
$table->foreignId('course_id')->constrained()->cascadeOnDelete();
$table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
$table->foreignId('adviser_id')->nullable()->constrained('users')->nullOnDelete();

// WRONG — mismatches auto-increment BIGINT, causes FK constraint errors:
$table->unsignedBigInteger('course_id');
$table->foreign('course_id')->references('id')->on('courses');
```

`foreignId()` is shorthand for `unsignedBigInteger` + auto-naming, and `constrained()` looks up the table by convention.
If your table name differs from the column convention, pass it explicitly: `->constrained('users')`.

---

## Rule 3: Be Explicit About Cascade Behavior

Always declare what happens to child records when a parent is deleted.

| Method | Effect | When to use |
|---|---|---|
| `->cascadeOnDelete()` | Child row deleted | Research authors when paper is deleted |
| `->restrictOnDelete()` | Deletion blocked if children exist | Courses that have papers |
| `->nullOnDelete()` | FK set to `NULL` | Papers that should remain after adviser deletion |
| `->noActionOnDelete()` | DB does nothing (not recommended) | Avoid |

```php
// In research_papers migration:
$table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
$table->foreignId('adviser_id')->nullable()->constrained('users')->nullOnDelete();
$table->foreignId('course_id')->constrained()->restrictOnDelete();
```

---

## Rule 4: Migration Order — Get This Right

This project has 7 domain tables. They MUST be created in dependency order:

```
users                ← created by Breeze (already exists)
  └── courses        ← created second (users.course_id references this)
        └── research_papers    ← FK references users + courses
              └── research_authors   ← FK references research_papers + users
              └── reviews            ← FK references research_papers + users
              └── defense_schedules  ← FK references research_papers
  └── announcements  ← FK references courses + users
```

**Timestamp naming rule:** The timestamp prefix determines migration order.
Use sequential numbers or ISO dates. Never create a child table migration with an earlier timestamp than its parent.

```
2026_04_09_000001_create_courses_table.php
2026_04_09_000002_add_role_and_course_to_users_table.php
2026_04_09_000003_create_research_papers_table.php
2026_04_09_000004_create_research_authors_table.php
2026_04_09_000005_create_reviews_table.php
2026_04_09_000006_create_defense_schedules_table.php
2026_04_09_000007_create_announcements_table.php
```

**If you get this wrong, you'll see:**
```
SQLSTATE[HY000]: General error: 1215 Cannot add foreign key constraint
```

---

## Rule 5: No Squashing on Laravel Cloud

On Laravel Cloud (production), **never run `php artisan schema:dump`** to squash migrations.
Each schema change = one new migration file. Never squash.

---

## Rule 6: Every `down()` Must Undo `up()` Exactly

```php
// If up() creates a table:
public function down(): void
{
    Schema::disableForeignKeyConstraints();
    Schema::dropIfExists('research_papers');
    Schema::enableForeignKeyConstraints();
}

// If up() adds a column:
public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('role');
    });
}
```

---

## Rule 7: `enum` Columns Must Match `casts` in Model

If you define `$table->enum('status', ['pending', 'revision', 'approved'])` in a migration,
your model **must** cast it and your code must only use those literal values.

```php
// app/Models/ResearchPaper.php
protected $casts = [
    'status' => 'string',
];
```

**Any `match()` expression must have a `default` arm:**
```php
$color = match($paper->status) {
    'approved' => '...',
    'revision' => '...',
    'pending'  => '...',
    default    => 'bg-gray-100 text-gray-700',
};
```

---

## Rule 8: Never Use `fake()` in Migrations

```php
// WRONG:
$table->string('name')->default(fake()->name());

// CORRECT:
$table->string('name')->default('Unknown');
$table->string('status')->default('pending');
```

Factories that use `fake()` belong only in `database/factories/`. Never in migrations.

---

## Pre-Migration Checklist

Before running `php artisan migrate` locally or deploying:

- [ ] `Schema::defaultStringLength(191)` is in `AppServiceProvider::boot()`
- [ ] All parent table migrations have earlier timestamps than child tables
- [ ] Every `up()` is wrapped in FK safety guards
- [ ] Every `down()` exactly reverses `up()`
- [ ] Enum values match model casts
- [ ] No `fake()` calls in migration files
