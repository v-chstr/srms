# UI Component Rules

Use this when editing Blade views or components.

## Main Rule

Do not repeat Blade markup. Search `resources/views/components/` first, use what exists, and extract a component only when a pattern repeats or clearly belongs to the shared design system.

## Component Folders

| Namespace | Folder | Use |
|---|---|---|
| `<x-ui.*>` | `components/ui/` | Buttons, badges, alerts, cards, modals, dates, detail fields, empty states |
| `<x-form.*>` | `components/form/` | Inputs, selects, textarea, file fields, checkboxes, validation errors |
| `<x-table.*>` | `components/table/` | Table wrapper, headings, cells |
| `<x-layouts.*>` | `components/layouts/` | App shell, guest shell, sidebar, page helpers |
| Domain folders | `components/admin`, `student`, `adviser`, `announcements`, etc. | Role or feature-specific UI |

## Before Writing Markup

Ask:

1. Does a component already exist for this element?
2. Is the same pattern already used elsewhere?
3. Would extracting a component reduce duplicated markup?
4. Is this only a one-off layout fragment?

Use the component for answers 1-3. Keep it inline for answer 4.

## Authoring Rules

- Declare props with `@props([...])` and safe defaults.
- Use `{{ $slot }}` and named slots for flexible content.
- Use `$attributes->merge()` or `$attributes->class()` so callers can pass attributes.
- Put conditional class maps inside reusable components.
- Every `match()` expression must include a `default` arm.
- Keep Tailwind classes in Blade/component files, never controllers.

## Required Shared Components

- Buttons: `<x-ui.button>`
- Flash alerts: `<x-ui.alert>`
- Status badges: `<x-ui.badge>`
- Form fields: existing `<x-form.*>` component when available
- Tables: existing `<x-table.*>` wrapper/heading/cell when available
- Modals: `<x-ui.modal>` and existing modal helper components
- Empty sections: `<x-ui.empty-state>`
- Label/value display: `<x-ui.detail-field>` when available

## Design Guardrails

- Use `primary-*`, `accent-*`, and `status-*` tokens from `tailwind.config.js`.
- No default Breeze visual styling.
- No `uppercase` or `tracking-*`.
- No `rounded-sm`.
- No dot separators such as `&middot;`.
- No emoji or em dashes in UI copy.
- Prefer the radius and spacing already used by the existing component, usually `rounded-md`.
- Do not add hover lift, scale transforms, heavy shadows, or decorative background effects.

## Layout Guidance

- Prefer the existing page/layout pattern for the area being edited.
- Use grids when the current page uses grids; do not force every page into a new bento structure.
- Keep empty states compact.
- Avoid nested cards.
- Do not put repeated table/card/action markup directly in multiple views.

## Verification

After Blade changes:

- Run `npm run build` if new Tailwind classes were introduced.
- Run `php artisan view:clear` after layout/component changes.
- Check the affected page for text overflow, repeated markup, and banned UI patterns.
