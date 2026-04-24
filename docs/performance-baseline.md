# Performance Baseline (2026-04-24)

## Scope
- Key runtime areas: `orders`, `employees`, `inv`, `tests`.
- Baseline collected before optimization rollout completion.

## Route Surface
- `orders`: 10 routes.
- `employees`: 9 routes.
- `inv`: 13 routes.
- `tests`: 12 routes.

## Static Risk Indicators (pre-optimization snapshot)
- Controller occurrences of `::all()` / `->get()` across `app/Http/Controllers`: high density on list/admin flows.
- Blade occurrences of model `::find()` calls: detected in multiple templates (runtime N+1 risk).

## Notes
- CLI in this environment prints unrelated git descriptor warnings during `php artisan route:list`, but route output is valid.
- Runtime latency baselines (p50/p95) should be measured in deployed environment with authenticated traffic and realistic data volume.
