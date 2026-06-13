# Cruding My Actor Scope Tokenized Resolver

`/my/` is an actor-scope prefix, not a resource token, not an operation token, and not an identity token.

The tokenized resolver checks prefixes in this order:

1. `/api/` transport prefix, handled by the API catch-all route.
2. `/my/` actor-scope prefix inside the captured CRUD path.
3. Standard CRUD token grammar.

Examples:

```text
/my/vendor/index
  actorScope: my
  resourcePath: vendor
  operation: index

/my/vendor/attachment/index
  actorScope: my
  resourcePath: vendor/attachment
  operation: index

/my/vendor/attachment/document/index
  actorScope: my
  resourcePath: vendor/attachment/document
  operation: index

/api/my/vendor/attachment/document/verify/acme-file
  routeFamily: tokenized_api_crud
  actorScope: my
  resourcePath: vendor/attachment/document
  operation: verify
  slug: acme-file
```

The `/my/` prefix does not require separate `*My*` entrypoint classes. Existing granular URI-derived services remain the default extension point:

```text
/my/vendor/attachment/index
  -> App\Service\Http\Vendor\Attachment\VendorAttachmentIndexService
```

A component service can branch on context if it needs scoped behavior:

```php
if ($context->isMyScoped()) {
    $userSlug = $context->actorUserSlug();
    $adminUserId = $context->actorAdminIdentityValue();
}
```

Actor scope values are exposed through `CrudEntrypointContext`:

```text
isActorScoped()
actorScope()
isMyScoped()
isActorGrounded()
actorUserId()
actorUserSlug()
actorUserIdentifier()
actorIdentityField()
actorIdentityValue()
actorAdminIdentityField()
actorAdminIdentityValue()
actorIsAdmin()
```

Frontend-facing actor identity defaults to `user_slug` when available, then `user_identifier`. Internal `user_id` is exposed as an admin identity only when the current security context has `ROLE_ADMIN` or `ROLE_SUPER_ADMIN`.

The CRUD engine is not duplicated. Registered service/key lookup remains first, URI-derived FQCN lookup remains second, and fail-soft fallback remains unchanged.
