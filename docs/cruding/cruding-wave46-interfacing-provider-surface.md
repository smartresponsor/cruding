# Cruding Interfacing Surface Contract

Cruding is limited to CRUD route processing, entity resolution, form handling, mutation guards, API responders, and neutral surface payload preparation.

## Browser rendering order

1. `@Interfacing/<resource>/index.html.twig` when a resource-specific Interfacing surface exists.
2. `@Interfacing/base.html.twig` as the shared Interfacing document fallback.
3. `@Cruding/crud/index.html.twig` as a minimal self-owned diagnostic/data fallback when Interfacing base is not available.

`index.html.twig` is the industrially cleaner concrete surface entry point. `base.html.twig` remains an inheritance/layout document, not the preferred resource render target.

## Bundle/config contract

`CrudingExtension` loads the bundle service configuration and prepends the `@Cruding` Twig namespace to `templates/`. The extension exposes a small host-facing configuration surface:

- `resource_path_requirement`
- `capability_map`
- `entity_class_alias_map`
- `form_type_map`

The default route requirement reserves CRUD/meta operation path segments only. Producer-specific exclusions, frontend section names, relation screens, and object-meta screens do not belong in this processor.

## Path normalization

Incoming `resourcePath` values are normalized before lookup/rendering. This keeps `/Product///Price/`, `product_price`, and similar aliases from producing divergent entity lookup, route-generation, or template-lookup behavior.

Cruding does not own platform menus, frontend provider selection, local shell CSS, object-meta screens, or relation screens in this cleaned slice.
