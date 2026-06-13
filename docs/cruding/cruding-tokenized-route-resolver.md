# Tokenized CRUD route resolver

Cruding no longer uses semantic Symfony route regex for CRUD grammar. Routes are structural delivery routes only.

```yaml
cruding_tokenized_catch_all:
    path: /{crudPath}
    controller: App\Cruding\Controller\Crud\CrudTokenizedController
    requirements:
        crudPath: '.+'
```

The resolver receives `crudPath`, tokenizes it, and applies the grammar in PHP:

```text
/vendor/index
  -> resourcePath=vendor
  -> operation=index

/vendor/acme-inc
  -> resourcePath=vendor
  -> operation=show
  -> slug=acme-inc

/vendor/attachment/media/edit/123
  -> resourcePath=vendor/attachment/media
  -> operation=edit
  -> id=123

/vendor/document/verify/acme-file
  -> resourcePath=vendor/document
  -> operation=verify
  -> slug=acme-file
```

The critical rule is positional: the token immediately before an identity slot is checked as a CRUD operation token regardless of URI depth.

Symfony Router does not decide whether a token is resource, operation, id, slug, or surface. Cruding owns that semantic layer.
