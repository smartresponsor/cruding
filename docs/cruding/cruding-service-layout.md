# Cruding service layout

`src/Service` contains one component responsibility root: `Crud`.

Role folders describe what a class does:

- `Api` — API response factories and API-specific helpers.
- `Entrypoint` — route-owned callable service resolution and invocation.
- `Operation` — executable CRUD use-case operations.
- `Runtime` — runtime inventory, lock, token, and route policy readers/guards.
- `view` — view route parsing, matching, provider lookup, and payload building.

Class names use the `Crud` prefix and an explicit role suffix such as `Resolver`, `Builder`, `Factory`, `Reader`, `Guard`, `Policy`, `Handler`, `Invoker`, `Locator`, `Provider`, or `Operation`.

The `Service` suffix is reserved for actual callable entrypoint services. Generic helpers must use their concrete role suffix instead.
