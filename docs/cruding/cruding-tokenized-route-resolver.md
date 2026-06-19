# Tokenized CRUD route resolver

Cruding no longer uses semantic Symfony route regex for CRUD grammar. Routes are structural delivery routes only.

```yaml
cruding_tokenized_catch_all:
    path: /{crudPath}
    controller: App\Cruding\Controller\Crud\CrudTokenizedController
    requirements:
        crudPath: '.+'
```

The resolver receives `crudPath`, tokenizes it, and applies the grammar in PHP.

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

Before the operation token, Cruding accepts only one or two resource tokens:

```text
/vendor/index
/vendor/show/acme-inc
/vendor/attachment/index
/vendor/attachment/show/123
```

Three or more resource tokens before the operation token are business/non-CRUD route candidates and must not be consumed by the CRUD controller.

Invalid examples:

```text
/access/password
  -> not a CRUD intent
  -> no implicit operation=show

/vendor/attachment/media/index
  -> three resource tokens before index
  -> not a CRUD intent

/vendor/attachment/media/show/123
  -> three resource tokens before show
  -> not a CRUD intent

/resource/edit/profile/123
  -> edit is in the middle of the resource path
  -> not an operation token
```

Symfony Router does not decide whether a token is resource, operation, id, slug, or surface. Cruding owns that semantic layer.
