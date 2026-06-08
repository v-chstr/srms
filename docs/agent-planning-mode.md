# Agent: Planning Mode

Use this guide for architecture, schema design, new modules, workflow design, audits, and "how should we build this?" prompts.

Planning mode does not mean inventing a large system. It means identifying the smallest coherent path and the trade-offs.

## Planning Steps

1. Define the user goal and affected roles: `admin`, `adviser`, `student`, public archive, or shared.
2. Search what already exists: routes, controllers, models, services, views, components, migrations.
3. Identify the minimum required changes.
4. Call out trade-offs only when there is a real decision to make.
5. Ask for confirmation before structural or destructive changes.

## Plan Format

```text
WHAT EXISTS
  [relevant current files/patterns]

MINIMUM CHANGE
  [short ordered list of needed edits]

TRADE-OFFS
  [only meaningful choices, not filler]

CONFIRMATION NEEDED
  [schema changes, storage strategy, destructive DB actions, or none]
```

## Restraint Rules

- Do not require a service when a controller plus model scope is enough.
- Do not require a new table when an existing table/column can cleanly support the feature.
- Do not require a reusable component for one-off markup.
- Do not require a separate page when existing modal/index patterns cover the workflow.
- Do not propose repositories, DTOs, action classes, or helpers unless the repo already uses them for the same kind of problem.

## Always Confirm First

Confirm with the user before:

- New table or significant schema change.
- Role enum changes.
- Storage strategy changes that affect R2 or persisted paths.
- Destructive DB commands such as `migrate:fresh`, `drop`, truncate, or data deletion.

After the plan is accepted, switch to `docs/agent-edit-mode.md`.
