# Tokenized CRUD route resolver

Cruding no longer uses semantic Symfony route regex for CRUD grammar. Routes are structural delivery routes only.

```yaml
cruding_tokenized_catch_all:
    path: /{crudPath}
    controller: App\Cruding\Controller\Crud\CrudTokenizedController
    requirements:
        crudPath: '.+'
```

The resolver receives `crudPath`, tokenizes it, strips context prefixes, and applies the grammar in PHP.

## Context prefix rule

The following leading tokens are route context, not resource path:

```text
my
api
ea
```

`ea` is the default backend context prefix and may be overridden by environment/config values such as `CRUDING_BACKEND_CONTEXT_TOKEN`, `CRUDING_BACKEND_ROUTE_TOKEN`, `CRUDING_BACKEND_ROUTE_PREFIX`, or `EASYADMIN_ROUTE_PREFIX`.

Context prefixes are stripped before CRUD resource depth is checked:

```text
/my/vendor/attachment/index
/api/my/vendor/attachment/index
/ea/api/my/vendor/attachment/show/acme-file
```

All three examples have the same semantic CRUD resource path depth after context-prefix trimming.

## Explicit operation token rule

Cruding must not infer a CRUD operation from an arbitrary trailing token. A multi-token URI without an explicit operation token is not a complete CRUD intent.

The operation token is valid only in one of these positions:

```text
/resource/index
  -> resourcePath=resource
  -> operation=index

/resource/show/acme-inc
  -> resourcePath=resource
  -> operation=show
  -> slug=acme-inc

/resource/attachment/index
  -> resourcePath=resource/attachment
  -> operation=index

/resource/attachment/edit/123
  -> resourcePath=resource/attachment
  -> operation=edit
  -> id=123
```

## CRUD resource depth rule

Before the operation token, Cruding accepts only one or two semantic resource tokens after context-prefix trimming:

```text
/vendor/index
/vendor/show/acme-inc
/vendor/attachment/index
/vendor/attachment/show/123
/my/vendor/attachment/index
/api/my/vendor/attachment/index
/ea/api/my/vendor/attachment/show/acme-file
```

Three or more semantic resource tokens before the operation token are business/non-CRUD route candidates and must not be consumed by the CRUD controller.

Invalid examples:

```text
/access/password
  -> not a CRUD intent
  -> no implicit operation=show

/vendor/attachment/media/index
  -> three semantic resource tokens before index
  -> not a CRUD intent

/my/api/ea/vendor/attachment/document/index
  -> context prefixes are stripped first
  -> vendor/attachment/document still has three semantic resource tokens
  -> not a CRUD intent

/vendor/attachment/media/show/123
  -> three semantic resource tokens before show
  -> not a CRUD intent

/resource/edit/profile/123
  -> edit is in the middle of the resource path
  -> not an operation token
```

Symfony Router does not decide whether a token is context, resource, operation, id, slug, or surface. Cruding owns that semantic layer.
