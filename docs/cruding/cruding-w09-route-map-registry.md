# Cruding W09 Route Map Registry

This pass adds a route-map registry adapter for zero-controller platform routes.

The goal is not to replace Symfony routing. The goal is to let Cruding keep its
existing catch-all parser routes while loading host-level route-map metadata:

```yaml
vendor.attachment.document.show_slug: { path: /vendor/attachment/document/show/{slug}, parser: cruding_surface_token_item, routeKey: vendor.attachment.document.show, object: attachment.document, template: document/show/index.html.twig, resolver: slug, service: App\Service\Http\Vendor\Attachment\Document\VendorAttachmentDocumentShowService }
```

## Why

Current surface grammar can match `/vendor/attachment/document/show/acme`, but
without a route-map override the provider key is reduced to the parsed shape.
The route-map keeps the full business chain:

```text
vendor.attachment.document.show
```

while still allowing a short Twig target:

```text
document/show/index.html.twig
```

## Runtime sources

Cruding scans the host project directory:

```text
config/platform/routes/**/*.yaml
```

## New command

```bash
php bin/console crud:runtime:route-map-audit
```

The command reports loaded entries and missing parser/resolver/service metadata.
