# Cruding W08 — Runtime lock decision guard

W08 adds a verification layer above the W06/W07 route matcher protection.

## Canon

- `APP_RUNTIME_SCOPE` is the requested runtime component/package set.
- `APP_RUNTIME_ENTITY` is the requested business/entity first-segment set used by route requirements.
- `APP_RUNTIME_SURFACE_TOKEN` is surface grammar, not a root entity set.
- `config/kernel/runtime_scope.*lock.php` is the accepted runtime decision lock.
- `composer.json` and `composer.lock` are the package inventory evidence.

`APP_RUNTIME_ENTITY` remains the route requirement source. W08 does not move this check into controllers and does not add a `/crud` prefix.

## Runtime lock examples

`config/kernel/runtime_scope.dev.lock.php`:

```php
<?php

return [
    'scope' => ['cruding', 'viewing', 'interfacing'],
    'entity' => ['alpha', 'attachment', 'media', 'product', 'category'],
    'surface_token' => ['show', 'index', 'card', 'table', 'gallery', 'compact', 'full', 'detail', 'list'],
    'packages' => [
        'cruding/crud',
        'viewing/view',
        'interfacing/interface',
    ],
];
```

Equivalent keys accepted by the reader:

- scope: `scope`, `runtime_scope`, `APP_RUNTIME_SCOPE`
- entity: `entity`, `runtime_entity`, `APP_RUNTIME_ENTITY`
- surface token: `surface_token`, `surface_tokens`, `runtime_surface_token`, `APP_RUNTIME_SURFACE_TOKEN`
- reserved: `reserved`, `reserved_tokens`, `runtime_reserved`, `APP_RUNTIME_RESERVED`
- packages: `packages`, `package_names`, `composer_packages`

## Command

```powershell
php bin/console crud:runtime:decision
```

This checks:

1. env-derived route policy from W06;
2. runtime lock file existence/content;
3. host `composer.json` declared packages;
4. host `composer.lock` installed packages;
5. expected package mapping for runtime scope tokens.

## Strict mode

Optional host config:

```yaml
cruding:
  route_guard:
    require_runtime_lock: true
    require_composer_packages: true
    scope_package_map:
      cruding: cruding/crud
      viewing: viewing/view
      interfacing: interfacing/interface
      administering: administering/administer
      accessing: accessing/access
```

In non-strict mode, missing lock/package evidence is reported as warnings where safe. In strict mode, missing required evidence fails the command.
