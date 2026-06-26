# Cruding tokenized route order before legacy view routes

Cruding must resolve canonical CRUD URI grammar through the tokenized resolver before legacy view routes are considered.

## Rule

The route import order is:

1. API tokenized CRUD routes
2. Web tokenized CRUD catch-all route
3. Legacy view routes as fallback compatibility routes

This prevents old view patterns from stealing canonical CRUD entrypoints.

## Examples

These paths must be delivered to the tokenized CRUD resolver:

```text
/vendor/attachment/index
/vendor/attachment/document/index
/vendor/attachment/media/edit/123
/vendor/document/verify/sample-entry
```

The tokenized resolver then derives:

```text
/vendor/attachment/index
  resourcePath = vendor/attachment
  operation = index

/vendor/attachment/document/index
  resourcePath = vendor/attachment/document
  operation = index

/vendor/attachment/media/edit/123
  resourcePath = vendor/attachment/media
  operation = edit
  id = 123
```

Legacy `cruding_resource_*` routes are retained only as compatibility fallback. They must not be imported before `cruding_crud`, otherwise paths such as `/vendor/attachment/index` can be misclassified as a view request and fall back to `vendor.index`.
