# AI Decision Tree Bootstrap Guide

> **What this file is:**
> A meta-prompt and guide for bootstrapping the same structured AI context system used in SRMS
> into any new project, regardless of tech stack.
>
> **How to use it:**
> 1. Fill in your project details in Section 2.
> 2. Copy the prompt from Section 3 verbatim.
> 3. Paste it into a fresh AI session (GitHub Copilot, Claude, ChatGPT, etc.).
> 4. The AI will generate all the doc files listed in Section 1.

---

## Section 1: What Gets Generated

Running the bootstrap prompt produces this file tree:

```
CLAUDE.md                               ← master auto-loaded context file
AGENTS.md                               ← agent instructions & conventions
docs/
  agent-planning-mode.md               ← decision tree for "how should we do X?" tasks
  agent-edit-mode.md                   ← decision tree for build/feature/bug-fix tasks
  agent-debug-mode.md                  ← structured protocol for broken/500 tasks
  dev-traits/
    learn.md                           ← living bug log (empty template with format)
    traits.md                          ← naming conventions & code patterns
    skills.md                          ← capability catalogue (what AI can/cannot do)
  notes/
    migration-standards.md             ← DB migration rules (robust, deployment-safe)
    backend-rules.md                   ← thin controllers, no N+1, validate-first
    ui-components.md                   ← component reuse rules (named for your stack)
```

**total:** 10 files + AGENTS.md. Every project should have all of them.

---

## Section 2: Fill In Your Project Details

Before copying the bootstrap prompt, answer these 13 questions about your project.
The more specific you are, the better the generated docs will be.

```
PROJECT_NAME:
  e.g. "SRMS", "PHC Mapping", "BEU AIS"

ONE_LINE_PURPOSE:
  e.g. "Student research management system for SPUP SITE"

BACKEND_STACK:
  e.g. "Laravel 13 + Eloquent ORM + MVC"

FRONTEND_STACK:
  e.g. "Laravel Blade + TailwindCSS + Alpine.js"

AUTH_SYSTEM:
  e.g. "Laravel Breeze (Blade stack) — role enum on users table"

ROLES:
  e.g. "admin / adviser / student"

DATABASE:
  e.g. "MySQL 8 on WAMP64 (local), Laravel Cloud MySQL (production)"

DEPLOYMENT_TARGET:
  e.g. "Laravel Cloud"

KEY_THIRD_PARTY:
  e.g. "Cloudflare R2 (S3 driver), barryvdh/laravel-dompdf"

COMPONENT_SYSTEM:
  e.g. "Laravel Blade components in resources/views/components/ — used as <x-ui.button>"

TEST_CREDENTIALS:
  e.g. "admin@srms.local / admin1234 | adviser@srms.local / adviser1234 | student@srms.local / student1234"

KNOWN_CONSTRAINTS:
  e.g. "No React/Inertia. TailwindCSS only. Schema::defaultStringLength(191) required."

DESIGN_ANTI_PATTERNS:
  List visual patterns from your OTHER projects that must NEVER appear in this one.
  This prevents design bleed between projects built by the same developer.
  e.g. "BEU-AIS uses: uppercase tracking-widest labels, &middot; dot separators,
  rounded-sm sharp corners, green primary color, top-nav with tracking-widest city tag."
  e.g. "PHC-Mapping uses: glassmorphism cards, gradient backgrounds, dark mode."
```

---

## Section 3: The Bootstrap Prompt

> **Copy everything from the line below to the end of this section.**
> Replace every `[BRACKETED PLACEHOLDER]` with your actual answers from Section 2.
> Paste into a new AI session and send.

---

```
You are a senior software engineer setting up a structured AI context documentation system for a new project. Your job is to generate all 10+ files of the AI decision tree system described below.

═══════════════════════════════════════════
PROJECT CONTEXT
═══════════════════════════════════════════
Name:              [PROJECT_NAME]
Purpose:           [ONE_LINE_PURPOSE]
Backend:           [BACKEND_STACK]
Frontend:          [FRONTEND_STACK]
Auth:              [AUTH_SYSTEM]
Roles:             [ROLES]
Database:          [DATABASE]
Deployment:        [DEPLOYMENT_TARGET]
Third-party libs:  [KEY_THIRD_PARTY]
Component system:  [COMPONENT_SYSTEM]
Test credentials:  [TEST_CREDENTIALS]
Known constraints: [KNOWN_CONSTRAINTS]
Design anti-patterns from other projects: [DESIGN_ANTI_PATTERNS]

═══════════════════════════════════════════
WHAT TO GENERATE
═══════════════════════════════════════════

Generate all files below, in order. Use markdown. Each file should be
complete and immediately usable — not a skeleton, not "fill this in later".

════════════════════════════════════
FILE 1 — CLAUDE.md (root of project)
════════════════════════════════════
The master auto-loaded context file. Structure with these sections:

§1  Project Definition — stack, auth, DB, deployment as bullet list
§2  Infer Your Mode — 3-row decision table (Planning / Edit / Debug)
§3  Domain Rules Table — maps code area to reference doc
§4  Hard Rules — 10-14 project-specific never-violate rules
§5  Session Report Format — template for post-task reporting
§6  Future Dev Notes — 5-8 planned features marked "(planned)"
§7  Known Inconsistencies — one entry about stale doc snippets
§8  Current Route Map — table with URL, middleware, who, named route
§9  Test Credentials — table of seeded accounts
§10 Design System — color palette, typography, component style rules
§10a Design Differentiation Lockdown — list every design anti-pattern from [DESIGN_ANTI_PATTERNS] as a prohibited pattern table. Include a 5-question pre-flight checklist for writing UI. This section is SAFETY-CRITICAL.

════════════════════════════════════
FILE 2 — AGENTS.md (root of project)
════════════════════════════════════
Agent instructions with sections:
/workflow, /approach, /stack, /backend, /blade, /ui (including Design Differentiation — Prohibited Patterns), /docs, /report, /avoid

════════════════════════════════════
FILE 3 — docs/agent-planning-mode.md
════════════════════════════════════
6-step planning protocol: understand scope, map stack, audit existing,
security implications, write plan, confirm before building.

════════════════════════════════════
FILE 4 — docs/agent-edit-mode.md
════════════════════════════════════
Decision tree for build tasks: CRUD, UI, routes, migrations, form bugs,
backend bugs, auth bugs. Pre-flight + post-flight checklists.

════════════════════════════════════
FILE 5 — docs/agent-debug-mode.md
════════════════════════════════════
Debug protocol: STOP rule, root cause report, log inspection,
error pattern tables (HTTP, PHP, deployment, Blade, storage).

════════════════════════════════════
FILE 6 — docs/dev-traits/learn.md
════════════════════════════════════
Living bug log with entry format template + 3-5 pre-populated entries
(MySQL key length, FK order, PHP attributes, Breeze redirect, middleware registration).

════════════════════════════════════
FILE 7 — docs/dev-traits/traits.md
════════════════════════════════════
Naming conventions, component namespaces, model conventions,
route conventions, JS conventions, DB conventions, security conventions.

════════════════════════════════════
FILE 8 — docs/dev-traits/skills.md
════════════════════════════════════
AI capability catalogue: proficiencies, anti-skills, code generation
capabilities, skill gaps, preferred patterns.

════════════════════════════════════
FILE 9 — docs/notes/migration-standards.md
════════════════════════════════════
8 rules: AppServiceProvider, FK guards, foreignId, cascade behavior,
migration order, no squashing, down() reversal, enum matching.

════════════════════════════════════
FILE 10 — docs/notes/backend-rules.md
════════════════════════════════════
7 rules: thin controllers, services, validation, N+1, scopes,
middleware role checks, storage abstraction.

════════════════════════════════════
FILE 11 — docs/notes/ui-components.md
════════════════════════════════════
Component directory structure, audit checklist (including design differentiation
checks from DESIGN_ANTI_PATTERNS), component examples
(button, badge, card, form input), layout system, design rules,
border radius reference table, text separator reference table.

════════════════════════════════════
FILE 12 — docs/notes/storage-rules.md (if file storage is used)
════════════════════════════════════
Upload/download patterns, dual-disk support, validation, resubmission,
security rules for private files.
```
