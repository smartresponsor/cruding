# Canonical current-actor CRUD page

This file is retained only because repository tooling does not currently permit physical file deletion.

The actor-scope URL grammar documented by the previous revision is removed. Current-user resolution is expressed by the terminal `page` operation without an explicit id or slug.

```text
/vendor/page
/attachment/page
/catalog/page
```

`page` may resolve the authenticated actor-owned object. `show` remains an explicit member operation. There is no legacy prefix compatibility or redirect contract.
