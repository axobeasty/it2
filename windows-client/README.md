# IT-Master Windows Client (WinForms, .NET Framework 4.8)

## What is implemented
- New desktop client skeleton to work with IT-Master without browser usage.
- API-first integration against existing backend (`/api/mobile/...`).
- Login form with token session restore.
- Login form with editable API Base URL (saved locally in user profile).
- Main shell with module navigation placeholders:
  - Schedule
  - Tests
  - Notifications
  - Orders
  - Inventory
  - Wiki
  - Users and roles
  - Settings
- Working module:
  - Schedule (week picker + table from API)
  - Tests (list + search, start attempt, typed answers, submit, copy selected question)
  - Test statistics (group filter + pagination page with prev/next + copy selected rows)
  - Notifications (bootstrap + polling by since_id + local search + copy selected notifications)
  - Wiki (list + open article + safe selection under filtering + copy slug)
  - Orders (my/all by rights, create order, admin status update incl. bulk select, quick detail row)
  - Inventory (my inventory and admin manage list + copy selected row)
  - Users and roles (employees/roles/groups tabs + bulk activate/deactivate employee + role/group assignment + groups CRUD + roles CRUD + role permissions)
  - Settings (general read/save)

## UX hotkeys added
- `Orders`: `Enter` in description creates order, `Ctrl+R` refreshes list.
- `Tests`: `Enter` in search starts selected test, `Ctrl+C` on questions copies selected question, double-click on test starts it.
- `Notifications`: `Ctrl+C` on table copies selected notifications.
- `Wiki`: `Enter` in search opens first filtered article.
- `Settings`: `Ctrl+S` saves, `Esc` resets form to last loaded values.

## Project path
- `windows-client/ItMaster.Desktop`

## Current backend contract used
- `POST /api/mobile/login`
- `GET /api/mobile/me`
- `GET /api/mobile/schedule`
- `GET /api/mobile/tests`
- `POST /api/mobile/tests/{id}/session`
- `POST /api/mobile/tests/{id}/submit`
- `GET /api/mobile/notifications?bootstrap=1`
- `GET /api/mobile/notifications?since_id=...`
- `GET /api/mobile/test-stats?group_id=&page=`
- `GET /api/mobile/wiki`
- `GET /api/mobile/wiki/{slug}`
- `GET /api/mobile/orders/categories`
- `GET /api/mobile/orders/my`
- `POST /api/mobile/orders/create`
- `PATCH /api/mobile/orders/{id}/status/{code}`
- `GET /api/mobile/inventory/my`
- `GET /api/mobile/inventory/manage`
- `GET /api/mobile/employees`
- `PATCH /api/mobile/employees/{id}/active/{state}`
- `PATCH /api/mobile/employees/{id}/assign`
- `GET /api/mobile/roles`
- `POST /api/mobile/roles`
- `PATCH /api/mobile/roles/{id}`
- `DELETE /api/mobile/roles/{id}`
- `GET /api/mobile/roles/{id}/permissions`
- `POST /api/mobile/roles/{id}/permissions`
- `GET /api/mobile/groups`
- `GET /api/mobile/settings/general`
- `POST /api/mobile/settings/general`

## Next implementation steps
1. Add typed API clients for each module (`schedule`, `tests`, `notifications`, etc.).
2. Build real screens module-by-module (starting with Schedule and Tests).
3. Add role/permission-aware UI (disable/hidden modules based on permissions).
4. Add centralized error handling and retry/offline strategy.
5. Add secure token storage (Windows DPAPI) and audit logging for desktop actions.
