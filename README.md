# Cruding

Cruding is the reusable Symfony bundle that owns generic CRUD route grammar, controller delivery, entity/form resolution, operation dispatch, resource-view payload production, API problem responses, and runtime CRUD diagnostics for host applications.

The canonical component namespace is `App\Cruding\`. The source tree is role-first: technical roles such as `Controller`, `Resolver`, `Builder`, `Factory`, `Provider`, `Service`, and `ServiceInterface` appear before feature branches such as `Api`, `Operation`, `Resource`, and `Runtime`. Class names retain the `Crud*` prefix; redundant intermediate `Crud/` folders are not canonical.

## Boundaries

Cruding owns generic CRUD mechanics. It does **not** own platform navigation, external callbacks/webhooks, host shell styling, or the final cross-component HTML rendering boundary.

## Runtime surface

- `App\Cruding\CrudingBundle` — bundle registration and compiler-pass integration.
- `src/Controller/` — generic CRUD and API controllers.
- `src/Resolver/` — context, entity, form, ownership, capability, and service resolution.
- `src/Service/Operation/` — executable CRUD operations.
- `src/Service/Resource/` — resource/view route and payload processing.
- `src/Service/Runtime/` — runtime token, lock, inventory, route-policy, and diagnostic behavior.
- `src/ServiceInterface/` — service contracts and mirrored operation/resource contracts where applicable.

## Local setup and quality gate

```bash
composer install
composer validate --strict
composer dump-autoload
composer check:cruding
```

`composer check:cruding` is the primary repository gate and includes canon checks, smoke guards, PHPDoc coverage, and PHPUnit.

## Local Composer path installation

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../Cruding",
      "options": { "symlink": true }
    }
  ],
  "require": {
    "cruding/crud": "*@dev"
  }
}
```

## Documentation


- [Cruding Resource View Routes](docs/cruding/cruding-resource-view-routes.md)
- [Entrypoint Migration Contract](docs/cruding/cruding-entrypoint-migration-contract.md)
- [Tokenized Route Resolver Details](docs/cruding/cruding-tokenized-route-resolver.md)
- [Host Route Integration Verification Guide](docs/cruding/cruding-host-route-integration-verification.md)

- [Component documentation map](docs/cruding/README.adoc)
- [Canonical service layout](docs/cruding/cruding-service-layout.md)
- [Resource-view routes](docs/cruding/cruding-resource-view-routes.md)
- [Tokenized route resolver](docs/cruding/cruding-tokenized-route-resolver.md)
- [Host route integration verification](docs/cruding/cruding-host-route-integration-verification.md)
- [Change log](CHANGELOG.md)
- [Contribution guide](CONTRIBUTING.md)
- [Security policy](SECURITY.md)

For the fuller architectural and integration overview, see `README.adoc`.

