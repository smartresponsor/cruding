# Cruding entrypoint operation/method parity

Cruding keeps granular, self-documenting component entrypoints. It must not collapse a resource into a single per-resource mega-service.

Canonical resolution order:

1. Explicit registered service key / service id.
2. URI-derived entrypoint class names under `App\Service\Http\...`.
3. Existing but unregistered class: fail-soft fallback with diagnostics.
4. Missing class: null entrypoint fallback with diagnostics.

Entrypoint class names are derived from normalized grammar:

```text
/{resourcePath}/{operationToken}/{id|slug?}
```

Examples using neutral sample resources:

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

The route controller may be a shared core controller, but the component extension point remains operation-specific because the operation token is preserved in `CrudContext::operation` and used by `CrudEntrypointClassNameResolver`.

All configured UI CRUD operation tokens must reach `CrudEntrypointOperationRunner` before falling back to the default Cruding operation flow:

```text
index
show
new
create
edit
update
delete
bulk
import
export
archive
restore
duplicate
```

Entrypoints may be empty, extend `AbstractCrudEntrypointService`, or implement one or more optional method interfaces. Absence of a class is not a crash condition.

Supported optional hooks:

```text
isGrounded(context)
get(context)
post(context)
put(context)
patch(context)
delete(context)
```

`CrudEntrypointContext` exposes helper methods for operation and HTTP-method checks, including `isOperation()`, `isGet()`, `isPost()`, `isPut()`, `isPatch()`, and `isDelete()`.

`null` from a hook means: continue with the default Cruding engine behavior.
