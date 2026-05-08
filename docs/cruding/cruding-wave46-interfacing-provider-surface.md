# Cruding Wave 46 — Interfacing provider surface rendering

Cruding no longer owns the primary visible CRUD shell for browser pages.

Cruding remains responsible for:

- resolving the resource context;
- loading objects;
- creating and handling forms;
- building access and action metadata;
- preserving mutation redirects and server-side ownership.

The final visible page is rendered through:

```text
Cruding controller/page definition
  -> CrudInterfacingProviderSurfaceBuilder
  -> interfacing/bridge/provider_surface.html.twig
  -> Interfacing provider document
  -> Ant Design ProComponents primary provider
  -> PrimeReact secondary provider
```

The old `templates/crud/*.html.twig` files are not deleted in this wave, but
Cruding CRUD controllers must not use them as the primary browser UI path.
They are legacy/local templates until a later cleanup wave removes or converts
remaining non-primary references.

Forbidden as primary rendering path:

- `render($page->template)` in visible CRUD controllers;
- `crud-app-shell` as the visible page shell;
- handmade Twig CSS as the primary UI surface;
- Bootstrap/EasyAdmin fallback UI.

Required primary rendering path:

- `interfacing/bridge/provider_surface.html.twig`;
- provider workbench metadata declaring `ant-design-procomponents` as primary;
- `primereact` as secondary/rich facade provider;
- Bridge/Interfacing ownership markers in the workbench diagnostics.
