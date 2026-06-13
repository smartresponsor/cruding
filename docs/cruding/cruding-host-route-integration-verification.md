# Cruding host route integration verification

Cruding now uses tokenized catch-all delivery routes. Symfony Router only delivers the request to Cruding; semantic CRUD grammar is resolved in PHP by `CrudTokenizedRouteIntentResolver`.

## Expected route names

```powershell
php bin/console debug:router cruding_tokenized_catch_all -vvv
php bin/console debug:router cruding_api_tokenized_catch_all -vvv
```

Expected delivery paths:

```text
cruding_tokenized_catch_all      /{crudPath}
cruding_api_tokenized_catch_all  /api/{crudPath}
```

## Expected matches

All of these match the same Symfony route and are then classified by Cruding:

```text
/alpha/index                         -> operation=index, resourcePath=alpha
/alpha/sample-entry                  -> operation=show, slug=sample-entry, resourcePath=alpha
/alpha/attachment/media/edit/123     -> operation=edit, id=123, resourcePath=alpha/attachment/media
/alpha/document/verify/sample-entry  -> operation=verify, slug=sample-entry, resourcePath=alpha/document
```

## Host check

```powershell
php bin/console router:match /alpha/index -vvv
php bin/console router:match /alpha/sample-entry -vvv
php bin/console router:match /alpha/attachment/media/edit/123 -vvv
php bin/console router:match /alpha/document/verify/sample-entry -vvv
```

`router:match` should show the catch-all route. The operation/resource/identity interpretation is intentionally not encoded in Symfony route regex; it is produced at runtime by Cruding diagnostics.
