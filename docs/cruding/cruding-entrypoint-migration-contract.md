# Cruding entrypoint migration contract

Cruding owns the CRUD engine. Components own small, self-documenting entrypoint classes derived from URI grammar.

The migration contract is intentionally read-only:

- `tools/cruding/entrypoint-map-audit.php` prints the entrypoint classes Cruding will look for.
- `tools/cruding/entrypoint-skeleton-preview.php` prints a skeleton preview for manual copy/review.
- Neither tool creates files, creates directories, or writes skeletons automatically.

## Resolution priority

1. Explicit registered service key / service id.
2. URI-derived granular entrypoint class.
3. Class exists but is not registered: fail-soft diagnostic.
4. Class missing: `NullCrudService` fallback.

The current registered service/key lookup remains first priority.

## URI-derived examples

```text
/alpha/index
  -> App\Service\Http\Alpha\AlphaIndexService

/alpha/show/123
  -> App\Service\Http\Alpha\AlphaShowService

/alpha/attachment/media/edit/123
  -> App\Service\Http\Alpha\Attachment\Media\AlphaAttachmentMediaEditService

/alpha/attachment/media/archive/sample-entry
  -> App\Service\Http\Alpha\Attachment\Media\AlphaAttachmentMediaArchiveService
```

The first candidate is the canonical self-documenting entrypoint. It includes the root resource, all nested resource tokens, and the operation token in the class name.

## Skeleton styles

Empty class:

```php
final class AlphaIndexService
{
}
```

Abstract defaults:

```php
final class AlphaIndexService extends AbstractCrudService
{
}
```

Optional method contract:

```php
final class AlphaIndexService implements CrudGetEntrypointInterface
{
    public function get(CrudEntrypointContext $context): ?CrudEntrypointResult
    {
        return null;
    }
}
```

## Non-goals

Do not create a required per-resource mega-service.

```text
BAD: AlphaCrudService as the required handler for all alpha operations.
GOOD: AlphaIndexService, AlphaShowService, AlphaAttachmentMediaEditService, etc.
```

Do not auto-create classes from Cruding. Migration tooling must be auditable and manual by default.
