# Cruding entrypoint contract finalization

Cruding entrypoints are granular, URI-derived extension points. They are not a per-resource mega-service contract.

## Resolution order

1. Explicit registered service/key from the route/request attributes.
2. URI-derived `App\Service\Http\...` entrypoint class candidates.
3. Class exists but is not registered: fail-soft null entrypoint with diagnostics.
4. Class missing: fail-soft null entrypoint with diagnostics.

The existing explicit service lookup remains first priority.

## Public contract

The stable public entrypoint API consists of:

- `CrudEntrypointContext`
- `CrudEntrypointResult`
- `CrudEntrypointResolution`
- `CrudEntrypointServiceInterface`
- `CrudGroundedEntrypointInterface`
- `CrudGetEntrypointInterface`
- `CrudPostEntrypointInterface`
- `CrudPutEntrypointInterface`
- `CrudPatchEntrypointInterface`
- `CrudDeleteEntrypointInterface`
- `AbstractCrudService`
- `PassiveCrudService`
- `NullCrudService`

## HTTP method hooks

Supported normalized HTTP hooks are:

- `get()`
- `post()`
- `put()`
- `patch()`
- `delete()`

A concrete entrypoint may implement only the hooks it needs. Missing hooks continue with Cruding default behavior.

## Grounding hook

`isGrounded()` is optional. If absent, Cruding treats the entrypoint as grounded. If present and it returns `false`, Cruding returns a fail-soft not-grounded result and continues default behavior when no payload is produced.

## Empty, abstract, interface, missing

- Empty entrypoint class: allowed; wrapped as passive entrypoint.
- Abstract base class: allowed; no-op defaults.
- Interface implementation: allowed; explicit hook contract.
- Missing class: allowed; null fallback with diagnostics.

## Status constants

Status values are defined by constants on `CrudEntrypointResult` and `CrudEntrypointResolution`. New code should not introduce ad-hoc status string literals inside the entrypoint runtime.

## Non-goals

Cruding must not require `VendorCrudService` or any other per-resource mega-service. URI-derived classes remain the primary self-documenting component extension shape, for example:

- `AlphaIndexService`
- `AlphaShowService`
- `AlphaAttachmentMediaEditService`
