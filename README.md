# Cruding

Cruding is a universal, metadata-driven CRUD route processor bundle for Symfony host applications. It automates common Create, Read, Update, and Delete endpoint routing, request decoding, form validation, and entity persistence.

This bundle is **not** an admin panel generator (like EasyAdmin) or a query builder. It serves as a middle-tier controller/routing engine that dynamically dispatches actions based on configuration maps.

## Current Posture

### What the component already does
- Automates route resolution for resource endpoints based on configured CRUD maps.
- Resolves actor scopes and security tokens using tokenized route resolvers.
- Invokes pre-save and post-save hooks safely.
- Maps JSON payloads directly to Doctrine entities using Symfony Forms.
- Registers route definitions dynamically via compiler passes.

### What this repository does not claim yet
- Custom user interface rendering for back-office tasks.
- Advanced database-level triggers or audit logs (handled in other layers).

## Runtime Surface & Entrypoints

The bundle intercepts incoming requests and configures mappings:
- `App\Cruding\CrudingBundle` - Registers the compiler pass and routing.
- `src/Controller/` - Contains the generic, catch-all CRUD controller.
- `src/Resolver/` - Tokenized and URI-based route resolvers.
- `src/Hook/` - Extension hooks for preflight and post-execution logic.

## Local Setup

Install dependencies:
```bash
composer install
```

Run test suite and verify provider surfaces:
```bash
vendor/bin/phpunit
composer run canon:cruding
```

## Local Composer Path Installation

To mount this bundle into your Symfony application:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../Cruding",
      "options": {
        "symlink": true
      }
    }
  ],
  "require": {
    "cruding/crud": "*@dev"
  }
}
```

## Documentation Map

- [Cruding Resource Surface Routes](docs/cruding/cruding-resource-surface-routes.md)
- [Entrypoint Migration Contract](docs/cruding/cruding-entrypoint-migration-contract.md)
- [Tokenized Route Resolver Details](docs/cruding/cruding-tokenized-route-resolver.md)
- [Host Route Integration Verification Guide](docs/cruding/cruding-host-route-integration-verification.md)
