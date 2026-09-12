# Tokenized CRUD route resolver

Cruding uses structural Symfony routes and resolves CRUD semantics in PHP.

## Canonical current-actor page

`page` is the canonical member operation when the current authenticated object must be resolved without an explicit id or slug.

```text
/vendor/page
/attachment/page
/catalog/page
```

A `page` request may resolve the object through the authenticated actor. `show` remains an explicit member operation and does not act as an implicit current-actor alias.

## Explicit operation token rule

A CRUD intent requires an explicit operation token. Valid examples:

```text
/resource/index
/resource/page
/resource/show/acme-inc
/resource/attachment/index
/resource/attachment/page
/resource/attachment/edit/123
```

## CRUD resource depth rule

Cruding accepts one or two semantic resource tokens before the operation token.

```text
/vendor/index
/vendor/page
/vendor/show/acme-inc
/vendor/attachment/index
/vendor/attachment/page
/vendor/attachment/show/123
```

Three or more semantic resource tokens before the operation token are business/non-CRUD route candidates and are not consumed as canonical CRUD resources.

The API transport prefix and backend routing remain transport concerns; they do not introduce a user-scope CRUD grammar token.

Symfony Router provides structural delivery. Cruding owns resource, operation, id, slug, view, and current-actor page semantics.
