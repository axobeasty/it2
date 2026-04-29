# Security Remediation Plan

## Goal
Reduce critical security risks in IT-Master and establish a repeatable secure development baseline.

## Priorities

### 1. Fix access control bypass in `page.access` (Critical)
- Update `EnsurePageAccess`: when `PageAccess::pathToPageKey()` returns `null` for protected web routes, deny access by default (403/redirect).
- Synchronize `PageAccess::MAP` with all relevant routes in `routes/web.php`, especially:
  - `/settings/email/test`
  - `/settings/git/*`
  - `/settings/database/*`
- Add a guard test/script that compares mapped routes vs actual routes to prevent future gaps.

### 2. Replace state-changing `GET` routes (High)
- Convert destructive/unsafe actions (`delete`, `activate`, `deactivate`, status changes) from `GET` to `POST/DELETE/PATCH`.
- Update Blade templates/forms to submit with CSRF tokens and proper HTTP method spoofing.
- Add temporary compatibility strategy if needed (deprecation redirect or hard fail after grace period).

### 3. Add brute-force protection for authentication (High)
- Add throttling middleware for `POST /auth`.
- Add throttling for `POST /api/mobile/login`.
- Prefer rate-limit keys using both IP and login identifier.
- Log repeated lockouts as security events.

### 4. Harden sensitive settings endpoints (High)
- Add defense-in-depth authorization checks directly in controllers for:
  - `/settings/git/*`
  - `/settings/database/*`
- Restrict execution to proper privileged permissions only.
- Add audit logging: actor, endpoint, payload summary, result.

### 5. Strengthen SMTP TLS policy (Medium)
- For production, enable strict TLS peer verification (`verify_peer=true`, `verify_peer_name=true`).
- Keep relaxed TLS mode only as explicit debug/fallback option behind config flag.

### 6. Review data visibility boundaries (Medium)
- Rework broad reads like `Portfolio::all()` where necessary.
- Enforce owner-only visibility by default; allow broader access only for explicit moderator/admin roles.

### 7. Session and cookie hardening (Medium)
- Verify production settings:
  - `SESSION_SECURE_COOKIE=true`
  - strict/lax `SESSION_SAME_SITE`
  - HTTPS-only transport
- Evaluate enabling `SESSION_ENCRYPT=true` if compatible.

### 8. Add security regression tests (Medium)
- Test that non-privileged users cannot access sensitive settings endpoints.
- Test that unmapped protected routes are denied.
- Test that old destructive GET routes are blocked.
- Test auth throttling behavior for web and mobile login.

## Suggested Release Sequence

### Release A (Hotfix)
- Item 1 (access bypass)
- Item 3 (auth throttling)

### Release B
- Item 2 (state-changing GET migration)

### Release C
- Items 4, 5, 6, 7 (hardening and policy)

### Release D
- Item 8 (security regression coverage in CI)

## Done Criteria
- No sensitive route is reachable without mapped permission.
- No destructive action is triggered via GET.
- Web/mobile auth endpoints are rate-limited and monitored.
- Security tests are part of CI and block regressions.
