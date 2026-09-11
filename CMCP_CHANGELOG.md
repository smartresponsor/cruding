# CMCP_CHANGELOG

## engine-20260911150014-cruding-34e436

### Iteration 1 — reconnaissance and baseline

- Target: `Cruding` (`cruding/crud`), Symfony bundle, PHP 8.4+, Symfony 8.1+.
- Current baseline: GitHub `master` at `7e672ebb75acec3f272c9c2544e61e7426376c29`.
- Read: root `AGENTS.md`, `README.md`, `composer.json`, route/controller/service wiring, route-map matcher tests, and the mandatory companion responsibilities for Objecting, Viewing, Interfacing, Gating, and Canonization.
- Canonization consulted: Canon008 Composer Dependency Integrity, Canon020 Typed Symfony Role Root, Canon021 Cruding Owns Generic CRUD; Gating mirrors for dependency integrity and documentation/runtime parity were also confirmed.
- Canon mapping: Cruding correctly owns generic CRUD routing/controllers; no foreign production namespace import was found that would justify inventing Objecting/Viewing/Interfacing Composer dependencies; role-first `Controller`/`Service`/`Resolver`/`Provider`/`Handler` roots remain valid.
- RC-critical finding: two independent implementations of `CrudRouteMapMatcher` exist at `src/Service/Crud/CrudRouteMapMatcher.php` and `src/Service/Crud/Resource/CrudRouteMapMatcher.php`. They initially contained the same matching algorithm, while separate callers used different classes, creating duplicate runtime responsibility and drift risk.
- Selected RC workstream: consolidate route-map matching on the resource-scoped canonical implementation, migrate runtime callers and service wiring, and verify no stale runtime ownership remains.
- Growth workstream: richer route collision/ambiguity diagnostics and developer-facing route-map introspection after RC; not an RC blocker.
- Additional bounded documentation debt observed: README documentation map referenced `cruding-resource-surface-routes.md` while the repository contains `cruding-resource-view-routes.md`.
- Material risks: service autowiring identity changes, stale namespace imports, tests silently covering only one duplicate implementation, and the explicit destructive-operation prohibition.
- Gates planned: Composer metadata review, repository smoke scripts, PHPUnit route-map tests, namespace/reference audit, PHP syntax checks for changed payloads, and post-change branch/PR/check inspection.

### Iteration 2 — material implementation

- Migrated `CrudController` to `App\Cruding\Service\Crud\Resource\CrudRouteMapMatcher`.
- Migrated `CrudRuntimeRouteMapAuditCommand` to the same canonical matcher.
- Updated `config/services.yaml` so explicit controller wiring targets the canonical matcher service.
- The initial removal of the old matcher file was reversed during verification because `Destructive operations: FORBIDDEN` has capability precedence.
- Restored `App\Cruding\Service\Crud\CrudRouteMapMatcher` as a thin compatibility facade that delegates `match()` and `entryList()` to the canonical resource matcher and contains no route matching algorithm.

### Iteration 3 — verification and fix

- Branch comparison confirmed the implementation changes are bounded to the route-map matcher consolidation plus this journal.
- Existing unit tests already instantiate and exercise the resource-scoped matcher (`CrudRouteMapMatcherTest`, `CrudRouteShapeResolverTest`).
- Runtime callers inspected during reconnaissance were the controller, runtime audit command, route-shape resolver, tests, and explicit service wiring; production callers now converge on the resource matcher while the old FQCN is a non-owning compatibility facade.
- PHP 8.4.23 syntax checks passed for the exact compatibility-facade payload and changed audit-command payload.
- `CrudController` changed only its matcher import; its previously valid runtime logic was otherwise unchanged.
- Full `composer install`, PHPUnit, Symfony container lint, Gating, and repository smoke execution could not be run because the authoritative Windows workspace/vendor tree is not mounted and the execution container has no outbound DNS for fetching the branch. This is recorded as an environment verification limitation, not treated as a green gate.

### Iteration 4 — debt closure and integration

- Fixed README documentation/runtime parity: `cruding-resource-surface-routes.md` was a nonexistent target; the documentation map now links the existing `docs/cruding/cruding-resource-view-routes.md`.
- No additional architectural expansion was introduced.
- No destructive final-state operation remains.
- Integration branch: `engine-20260911150014-cruding-34e436`.
- Pull request opened: `#5` — `Consolidate Cruding route-map matcher ownership`.
- GitHub recalculated the PR as mergeable with no conflicts.

### Iteration 5 — final acceptance and handoff

- Final PR mergeability before this journal update: `true`.
- Head commit had no GitHub combined-status entries and no pull-request workflow runs.
- Because full local Composer/PHPUnit/Gating/Symfony checks cannot run in the available environment and no CI runner supplied equivalent evidence, the merge quality gate is not asserted green.
- PR #5 is intentionally left open rather than merged without verification evidence.
- Bounded implementation work is complete; the remaining tail is verification/integration evidence, not additional Cruding implementation.

### Что имеем?
One canonical route-map matching implementation under `Service/Crud/Resource`, production runtime callers wired to it, a non-owning compatibility facade retained solely because destructive operations are forbidden, corrected route documentation linkage, and a conflict-free integration PR.

### Что осталось?
Run the repository quality gates in the authoritative workspace or CI (`composer validate`, `composer run check:cruding`, PHPUnit, Symfony/container/YAML checks, applicable Gating). If green, PR #5 is ready for merge; no further implementation work is currently identified.
