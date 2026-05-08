# Cruding Wave 48 — Interfacing asset runtime route

Cruding can run as the active host/runtime during local integration, but it must not own
primary UI rendering. Wave 48 exposes the canonical Interfacing admin-body asset URLs
from the current Symfony runtime so the already-rendered provider document can hydrate
instead of staying at the boot marker.

Canonical path remains:

- Cruding owns resource/data/form context.
- Bridge/Interfacing provider surface owns UI rendering.
- Ant Design ProComponents remains the primary provider.
- PrimeReact remains the secondary/rich-facade provider.
- Cruding handmade Twig CSS is not restored as a primary UI path.

The route `/interfacing/admin-body/{assetPath}` is loaded before generic Cruding resource
routes so provider assets cannot be swallowed by `{resourcePath}` catch-all routes.
