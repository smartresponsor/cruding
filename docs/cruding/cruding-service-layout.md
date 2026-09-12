# Cruding service layout

Cruding is a component repository with the canonical PSR-4 root `App\Cruding\` mapped directly to `src/`.

The component token is already expressed by both the namespace root and the `Crud*` class prefix. It must not be repeated as an intermediate directory such as `Service/Crud`, `Resolver/Crud`, or `Controller/Crud`.

## Role-first tree

Top-level folders describe the technical role first: `Controller`, `Resolver`, `Builder`, `Factory`, `Handler`, `Provider`, `Guard`, `Invoker`, `Dispatcher`, `Runner`, `Parser`, `Service`, and `ServiceInterface`.

Feature semantics appear only after the technical role. Current `Service` feature branches are:

- `Service/Api` — API-specific response/problem helpers;
- `Service/Operation` — executable CRUD operation services;
- `Service/Resource` — resource/view route and payload processing;
- `Service/Runtime` — runtime inventory, lock, token, and route-policy behavior.

Class names use the `Crud` prefix and an explicit role suffix such as `Resolver`, `Builder`, `Factory`, `Reader`, `Guard`, `Policy`, `Handler`, `Invoker`, `Locator`, `Provider`, or `Operation`.

The `Service` suffix is reserved for actual callable entrypoint services. Generic helpers must use their concrete role suffix instead.

DTO classes live under `DTO/` and use the explicit `DTO` suffix. Capability contracts live under `Contract/Capability`; service contracts live under `ServiceInterface`.

The service-layout, role-suffix, DTO-suffix, and PHPDoc smoke guards enforce these conventions.
