# Cruding W09 Route Map Registry

This pass adds a route-map registry adapter for zero-controller platform routes.

The goal is not to replace Symfony routing. The goal is to let Cruding keep its
existing catch-all parser routes while loading host-level route-map metadata:

```yaml
alpha.attachment.document.show_slug: { path: /alpha/attachment/document/show/{slug}, parser: cruding_surface_token_item, routeKey: alpha.attachment.document.show, object: attachment.document, template: document/show/index.html.twig, resolver: slug, service: App\Service\Http\Alpha\Attachment\Document\AlphaAttachmentDocumentShowService }
```

## Why

Current surface grammar can match `/alpha/attachment/document/show/sample-entry`, but
without a route-map override the provider key is reduced to the parsed shape.
The route-map keeps the full business chain:

```text
alpha.attachment.document.show
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
