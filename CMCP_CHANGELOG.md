# CMCP_CHANGELOG

## engine-20260911150014-cruding-34e436

### Iteration 1 — reconnaissance and baseline

- Target: `Cruding` (`cruding/crud`), Symfony bundle, PHP 8.4+, Symfony 8.1+.
- Current baseline: GitHub `master` at `7e672ebb75acec3f272c9c2544e61e7426376c29`.
- Read: root `AGENTS.md`, `README.md`, `composer.json`, route/controller/service wiring, route-map matcher tests, and the mandatory companion responsibilities for Objecting, Viewing, Interfacing, Gating, and Canonization.
- Canonization consulted: Canon008 Composer Dependency Integrity, Canon020 Typed Symfony Role Root, Canon021 Cruding Owns Generic CRUD; Gating mirrors for dependency integrity and documentation/runtime parity were also confirmed.
- Canon mapping: Cruding correctly owns generic CRUD routing/controllers; no foreign production namespace import was found that would justify inventing Objecting/Viewing/Interfacing Composer dependencies; role-first `Controller`/`Service`/`Resolver`/`Provider`/`Handler` roots remain valid.
- RC-critical finding: two independent implementations of `CrudRouteMapMatcher` exist at `src/Service/Crud/CrudRouteMapMatcher.php` and `src/Service/Crud/Resource/CrudRouteMapMatcher.php`. They currently contain the same matching algorithm, but separate callers use different classes, creating duplicate runtime responsibility and drift risk.
- Selected RC workstream: consolidate route-map matching on the resource-scoped canonical implementation, migrate runtime callers and service wiring, remove the confirmed duplicate, and verify no stale references remain.
- Growth workstream: richer route collision/ambiguity diagnostics and developer-facing route-map introspection after RC; not an RC blocker.
- Additional bounded documentation debt observed: README documentation map references `cruding-resource-surface-routes.md` while the current repository contains `cruding-resource-view-routes.md`; address only after runtime consolidation if still factual.
- Material risks: service autowiring identity changes, stale namespace imports, and tests silently covering only one duplicate implementation.
- Gates planned: Composer metadata review, repository smoke scripts, PHPUnit route-map tests, namespace/reference search, and post-change branch diff/CI status inspection.

### Что имеем?
A bounded, factual duplicate-runtime-responsibility defect with confirmed callers and an existing tested canonical resource route-map surface.

### Что осталось?
Material implementation, verification/fix pass, debt closure/integration, and final acceptance.
