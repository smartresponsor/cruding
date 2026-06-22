# Cruding URI-derived entrypoint extension

Cruding owns the CRUD engine. Components may expose self-documenting extension points as granular HTTP services derived from the normalized URI grammar.

## Resolution order

1. Explicit registered service key from request attributes.
2. URI-derived `App\Service\Http\*` service class candidates.
3. Class exists but is not registered: diagnostic fallback.
4. Missing class: null fallback.

This keeps the existing registered service/key lookup as the highest priority while avoiding a mandatory per-resource mega-service.

## Class naming

`/{resourcePath}/index` is delivered through `/{crudPath}` and tokenized by Cruding into an operation-specific entrypoint class.

Examples:

- `/alpha/index` -> `App\Service\Http\Alpha\AlphaIndexService`
- `/alpha/show/123` -> `App\Service\Http\Alpha\AlphaShowService`
- `/alpha/attachment/media/edit/123` -> `App\Service\Http\Alpha\Attachment\Media\AlphaAttachmentMediaEditService`
- `/alpha/attachment/media/archive/sample-entry` -> `App\Service\Http\Alpha\Attachment\Media\AlphaAttachmentMediaArchiveService`

These classes are extension points, not controllers. They may be empty, extend `AbstractCrudService`, or implement one or more optional entrypoint interfaces.

## Safe service shapes

Empty service:

```php
final class AlphaIndexService
{
}
```

Abstract default service:

```php
final class AlphaIndexService extends AbstractCrudService
{
}
```

Method-specific service:

```php
final class AlphaIndexService implements CrudGetEntrypointInterface
{
    public function get(CrudEntrypointContext $context): CrudEntrypointResult|Response|CrudSurfaceContract|null
    {
        return null;
    }
}
```

## HTTP method hooks

The invoker supports optional `get`, `post`, `put`, `patch`, and `delete` hooks. Missing hooks continue into the default Cruding operation implementation.

`isGrounded(CrudEntrypointContext $context)` is optional. If it returns `false`, Cruding records a not-grounded result and continues with the default operation path rather than throwing.

## Operation/method parity

All configured UI CRUD operation tokens must keep the same URI-derived entrypoint behavior. Operation aliases such as `update`, `archive`, `restore`, `duplicate`, `import`, `export`, and `bulk` may use shared core operation classes internally, but the public extension point remains the URI-derived class that includes the actual operation token.

Example:

```text
/alpha/attachment/media/archive/sample-entry
  -> App\Service\Http\Alpha\Attachment\Media\AlphaAttachmentMediaArchiveService
```

This is intentionally not collapsed into a single per-resource service.
