# Cruding legacy invokable bridge and config-driven operation router

This pass keeps the stabilized URI-derived entrypoint model and adds two compatibility extensions.

## Legacy invokable bridge

Cruding still prefers explicit HTTP method hooks:

- `get(CrudServiceContext $context)`
- `post(CrudServiceContext $context)`
- `put(CrudServiceContext $context)`
- `patch(CrudServiceContext $context)`
- `delete(CrudServiceContext $context)`

If no method-specific hook returns a payload and the resolved entrypoint is publicly invokable, Cruding calls the legacy `__invoke()` fallback with the Symfony request object.

This keeps existing granular component services compatible while preserving the newer explicit hook model.

## Config-driven operation tokens

CRUD operation tokens are configured in `config/cruding_reserved_token.yaml`.

Core operations keep their explicit routes. Additional configured operations are routed by generic operation-token routes:

- `/{resourcePath}/{operationToken}`
- `/{resourcePath}/{operationToken}/`
- `/{resourcePath}/{operationToken}/{id}`
- `/{resourcePath}/{operationToken}/{id}/`
- `/{resourcePath}/{operationToken}/{slug}`
- `/{resourcePath}/{operationToken}/{slug}/`

The controller normalizes `operationToken` into `_crud_operation` and invokes the URI-derived entrypoint resolver.

Examples:

- `/alpha/category/assign`
- `/alpha/document/verify/123`
- `/alpha/payout/pay/sample-entry`

The operation token is never treated as a slug or embedded resource-path token.

## Non-goals

- No component-specific route hardcode.
- No `VendorCrudService` mega-service requirement.
- No business/public route shield.
- No Port/Adapter layout.
- No `src/Domain` layout.
