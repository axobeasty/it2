# Full Project Audit (2026-05-05)

## Scope
- Full audit of current Laravel monolith: architecture, security, data/migrations, operations.
- Goal: prepare a reliable baseline for building a new project on top of current business domain.

## Executive Summary
- Current system is a feature-rich Laravel monolith with meaningful domain coverage (schedule, testing, inventory, orders, wiki, roles).
- Main systemic risk is high coupling in controllers and operationally dangerous web-driven admin/deploy flows.
- Security posture is mixed: central permission mapping exists, but several high-impact weaknesses remain.
- Data model is workable, but legacy areas have missing FKs/constraints and migration determinism issues.
- Recommended strategy: reuse selected domain modules, rewrite auth + settings/deploy orchestration, and formalize release process.

## 1) Architecture Audit

### 1.1 Current architecture map
- Entry points:
  - `routes/web.php`
  - `routes/api.php`
  - `bootstrap/app.php`
- Core cross-cutting controls:
  - `app/Http/Middleware/EnsurePageAccess.php`
  - `app/Support/PageAccess.php`
  - `app/Models/Employee.php` (`canAccessPage`)
- High-impact controllers:
  - `app/Http/Controllers/AuthController.php`
  - `app/Http/Controllers/SettingsController.php`
  - `app/Http/Controllers/StudentTestingController.php`
  - `app/Http/Controllers/GroupScheduleController.php`
  - `app/Http/Controllers/MobileApiController.php`

### 1.2 Coupling hotspots
- Session/user checks and redirects duplicated across many controllers.
- Route-level permissions are string-mapped and require synchronized updates in route files and permission map.
- `Settings` singleton (`id=1`) is deeply embedded in app flows.
- Fat controllers mix domain logic + infrastructure/process execution.

### 1.3 Architectural risks for next project
- Permission regressions likely when adding routes.
- Hard to unit test critical behavior due to controller size and side effects.
- Auth behavior divergence between web and mobile paths.
- Operational concerns (deploy, migrate, env writes) mixed into HTTP layer.

## 2) Security Audit

## Severity: Critical
- Sensitive runtime config and key material are present in tracked `.env` in this workspace snapshot.
  - File: `.env`
  - Risk: secret leakage, insecure diagnostics in production-like usage.
- Privileged infra actions are available via web endpoints in settings module.
  - File: `app/Http/Controllers/SettingsController.php`
  - Risk: admin session compromise can lead to code/deploy/db impact.

## Severity: High
- Session lifecycle hardening is incomplete in custom auth flow.
  - File: `app/Http/Controllers/AuthController.php`
  - Risk: fixation/session hygiene weaknesses.
- Ownership checks are inconsistent on mutation endpoints (example class of risk: IDOR).
  - File: `app/Http/Controllers/TaskController.php`
- Wiki content rendering uses raw HTML output path.
  - Files:
    - `app/Http/Controllers/WikiController.php`
    - `resources/views/wiki/show.blade.php`
  - Risk: stored XSS if sanitization assumptions fail.

## Severity: Medium
- Validation depth is inconsistent on some legacy write endpoints.
  - Files:
    - `app/Http/Controllers/EmployeeController.php`
    - `app/Http/Controllers/TaskController.php`
    - `app/Http/Controllers/ProfileController.php`
- Logout over GET increases CSRF-style nuisance risk.
  - File: `routes/web.php`

## Security quick wins
1. Enforce production env hardening (`APP_ENV=production`, `APP_DEBUG=false`) and rotate secrets if leaked.
2. Move deploy/migrate/infra actions out of HTTP surface.
3. Standardize login/logout session lifecycle hardening.
4. Add ownership/policy checks to all write/delete handlers.
5. Apply explicit HTML sanitization for wiki render pipeline.

## 3) Data and Migration Audit

### 3.1 Data domains (high level)
- Identity and org: roles, employees, departments, groups, faculties, chairs.
- Learning flow: tests, questions, assignments, attempts.
- Scheduling: schedule entries + constructor settings/subjects.
- Content and ACL: wiki pages + role pivot.
- Legacy operational areas: orders, inventory, portfolio.

### 3.2 Structural risks
- Legacy tables in orders/inventory/portfolio areas miss FK constraints by design.
- Repeated create/ensure migration patterns reduce migration determinism.
- Schema+data logic is mixed in some migrations (permission seeding/updates).
- Missing unique constraints for key identity fields can permit duplicates.

### 3.3 Migration criticality for rewrite
- Critical first: identity/roles/permissions + wiki ACL base.
- High second: tests and schedule domains.
- Medium third: orders/inventory/portfolio (requires cleanup + constraint enforcement).
- Low/operational last: sessions, notifications, delivery logs.

## 4) Operations and Release Audit

### 4.1 Current strengths
- Dual update model supports git-clone and ftp/manual environments.
- Deploy ref tracking via `storage/app/deploy.json` / `DEPLOY_GIT_REF`.
- Update-check UX gives actionable operator feedback.

### 4.2 Operational risks
- If code update succeeds and migration fails, rollback is not fully automated.
- No first-class backup gate before destructive DB operations.
- Runtime web-triggered deploy/migrate flow increases blast radius.
- Scheduler/monitoring/alerting posture is limited for production operations.

### 4.3 Runbook baseline (minimum safe)
1. Preflight: tests + migration review + backup success required.
2. Deploy artifact immutably.
3. Run migrations with failure handling and explicit rollback decision tree.
4. Smoke-check critical routes and background jobs.
5. Persist deployed SHA + release in a canonical version marker.

## 5) Audit Conclusions
- Project is functionally strong but structurally costly to evolve safely as-is.
- New project should preserve domain knowledge, not controller-level implementation.
- Foundation priorities before/with rewrite:
  - auth/authorization redesign,
  - deployment hardening,
  - data integrity normalization,
  - modular service extraction.

