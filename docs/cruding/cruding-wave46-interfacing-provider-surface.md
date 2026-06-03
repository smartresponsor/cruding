# Cruding Interfacing Surface Contract

Cruding is limited to CRUD route processing, entity resolution, form handling, mutation guards, API responders, and neutral surface payload preparation.

## Browser rendering boundary

Cruding stops at `CrudSurfaceContract`. Viewing owns the browser render decision and may render through Interfacing or return structured JSON. The canonical runtime fallback chain is:

1. `@Interfacing/<surface-or-resource>/index.html.twig`
2. `@Interfacing/index.html.twig`
3. `@<Component>/index.html.twig`
4. `@Viewing/view/index.html.twig`

Cruding may expose diagnostic folder/index hints, but those hints are not the runtime render contract. Operation tokens such as `briefing`, `show`, or `edit` are provider/view context and must not become required physical Twig files.

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
