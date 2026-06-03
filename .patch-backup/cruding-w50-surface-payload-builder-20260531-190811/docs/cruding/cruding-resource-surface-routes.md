# Cruding resource-surface route engine

Cruding treats a resource-bound route as the producer-side declaration. The route tokens are parsed into a neutral context and then used to locate a producer surface provider by naming convention.

## Route grammar

Supported read-oriented shapes:

```text
/{resource}/{subject}/{surface}
/{resource}/{subject}/{surface}/{action}
/{resource}/{subject}/{surface}/{item}/{action}
```

Examples:

```text
/vendor/acme-inc/compliance/briefing
/vendor/acme-inc/document/w9-form/preview
/product/demo-product/offer/summary
```

The route path decides the resource, subject, surface and action. No operation descriptor is required for these resource-bound surface routes.

## Provider convention

For `/vendor/acme-inc/compliance/briefing`, Cruding computes these provider keys:

```text
vendor.compliance.briefing
vendor.briefing
```

A producer provider named `VendorComplianceBriefingSurface` implements `CrudSurfaceProviderInterface` and is autoconfigured with the `cruding.surface_provider` tag.

Providers must return `CrudSurfaceContract`. They must not return Symfony `Response`, `JsonResponse`, HTML, or render Twig templates.

## Payload contract

The controller depends only on `CrudSurfaceContract`. The contract carries a stable envelope and a free producer-owned location map:

```text
top
left
body
right
bottom
stickyTop
stickyBottom
tool
filter
menu
diagnostic
```

Producer providers decide which locations and blocks exist. Viewing/Interfacing decides whether to render a template or return JSON.

## Generic fallback

If no producer provider exists, Cruding falls back only for generic read routes:

```text
index
detail/show/view
```

Mutation routes (`new`, `edit`, `delete`) remain on the existing CRUD controllers in this wave.

## Wave 48 diagnostics

Cruding now exposes two host-application diagnostics for resource-bound surface routes:

```bash
bin/console crud:surface:debug-map
bin/console crud:surface:debug-map --providers --templates
bin/console crud:surface:lint-routes
bin/console crud:surface:lint-routes --strict-provider
```

`crud:surface:debug-map` is intentionally diagnostic only. It shows how every route handled by `CrudSurfaceController` is parsed into resource, subject, surface, operation, provider key, provider status, and template candidate.

`crud:surface:lint-routes` validates the route-token convention without requiring producer-specific descriptors. Generic catch-all routes such as `/{resource}/{subject}/{surface}/{action}` are allowed to resolve providers at runtime. Concrete non-fallback routes can be made strict with `--strict-provider`, which is useful inside a host application after producer components are registered.

The important boundary remains unchanged: the route path is the producer-side declaration, the controller is universal, and producers provide only convention-named services that implement `CrudSurfaceProviderInterface`.


## Diagnostics notes

Generic Cruding surface routes are declarations, not concrete producer routes.
When `crud:surface:debug-map` reports `Provider: missing` for routes such as
`/{resource}/{subject}/{surface}/{action}`, that only means no producer-specific
`cruding.surface_provider` service has been registered in the host container yet.
The route still resolves the provider key at runtime from the actual URL tokens.

`crud:surface:lint-routes --strict-provider` intentionally skips hard provider
failure for generic declarations. Strict provider failure is meaningful for
concrete producer routes, because their provider key is no longer just a sample.

## Producer payload builder

Producer surface providers should return `CrudSurfaceContract` and should not build `Response`, `JsonResponse`, HTML, or Twig output. For normal producer routes the small builder keeps payload shape uniform while allowing each component to choose its own locations and block data.

```php
use App\Cruding\Dto\Surface\CrudSurfaceRequest;
use App\Cruding\Service\Surface\CrudSurfacePayloadBuilder;
use App\Cruding\ServiceInterface\Surface\CrudSurfaceProviderInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;

final readonly class VendorComplianceBriefingSurface implements CrudSurfaceProviderInterface
{
    public function provide(CrudSurfaceRequest $request): CrudSurfaceContract
    {
        return CrudSurfacePayloadBuilder::fromRequest($request)
            ->title('Vendor compliance briefing')
            ->block('top', 'vendor_header', ['slug' => $request->routeContext->subjectValue])
            ->block('body', 'compliance_briefing', ['riskLevel' => 'medium'])
            ->block('right', 'next_action', ['count' => 2])
            ->toContract();
    }
}
```

The controller depends only on `CrudSurfaceContract`. It does not inspect or branch on `vendor_header`, `compliance_briefing`, `next_action`, or any producer-specific data volume. Viewing/Interfacing receives the stable envelope and chooses template rendering or JSON fallback.
