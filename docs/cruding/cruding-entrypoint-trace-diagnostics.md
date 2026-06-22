# Cruding EntryPoint Trace Diagnostics

Cruding entrypoint resolution is fail-soft, but it must also be observable. The operation runner exposes a diagnostic result path without changing the existing payload-oriented API.

## Contract

- `CrudServiceRunner::run()` returns the full `CrudEntrypointResult`.
- `CrudServiceRunner::tryRun()` keeps the existing `Response|CrudSurfaceContract|null` API.
- Every `run()` result carries `entrypointTrace` diagnostics.
- The trace includes HTTP method, route name, path, resource path, operation, result status, payload/default decision, and service resolution details.

## Resolution order remains unchanged

1. Explicit registered service key / service id.
2. URI-derived `App\Service\Http\*` entrypoint class.
3. Class exists but is not registered: fail-soft fallback.
4. Missing class: null fallback.

## Why this exists

A missing, empty, abstract-based, interface-based, or failing entrypoint must not crash Cruding. Diagnostics must still make the decision visible so host applications can audit why default CRUD behavior continued.
