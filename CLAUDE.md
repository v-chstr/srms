# CLAUDE.md - SRMS Quick Guide

Read `AGENTS.md` first. It is the canonical AI guide for this project.

This file stays short on purpose so Claude sessions do not start with duplicated or stale instructions.

## Non-Negotiables

- Laravel 13, Blade, TailwindCSS, Alpine.js. No React, Inertia, or Vue.
- Use the existing Laravel/Breeze auth foundation, but keep SRMS views custom.
- Roles are `admin`, `adviser`, and `student`; admins can advise only when `is_adviser=true`.
- Search existing code and components before creating new files.
- Use the smallest useful implementation. Avoid extra layers.
- Controllers stay lean. Services are for real business logic, not CRUD pass-through.
- No repeated Blade markup. Use or extract components when a UI pattern repeats.
- Validate input before persistence. Never use `$request->all()` for mass assignment.
- Use `Storage::disk()` for files and store relative paths.
- Seeders must be rerunnable.
- Never run destructive database commands without confirmation.

## What To Read For The Task

| Task | Read |
|---|---|
| New feature or code edit | `docs/dev-traits/learn.md`, then `docs/dev-traits/traits.md` |
| Bug, error, or regression | `docs/agent-debug-mode.md` |
| Planning, schema, architecture | `docs/agent-planning-mode.md` |
| Controller, service, query | `docs/notes/backend-rules.md` |
| Blade or UI component | `docs/notes/ui-components.md` |
| Migration or seeder | `docs/notes/migration-standards.md` |
| Uploads, downloads, R2 | `docs/notes/storage-rules.md` |

## UI Guardrails

- Academic, clean, restrained.
- Use `primary-*`, `accent-*`, and `status-*` tokens from `tailwind.config.js`.
- No default Breeze styling.
- No `uppercase`, `tracking-*`, dot separators, `rounded-sm`, emoji, or em dashes in UI copy.
- Status output goes through `<x-ui.badge>`.
- Repeated fields, cards, modals, tables, buttons, and alerts should use components.

## Session Report

End completed work with the report format from `AGENTS.md`.
