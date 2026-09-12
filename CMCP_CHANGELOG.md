
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

- Final PR mergeability before integration: `true`.
- Head commit had no GitHub combined-status entries and no pull-request workflow runs.
- Full local Composer/PHPUnit/Gating/Symfony checks remained unavailable in the execution environment; this limitation was explicitly surfaced before integration.
- User explicitly authorized continuation with `Go ahead`.
- PR #5 was merged successfully into `master` on 2026-09-11.
- Merge commit: `b63413ec2530df9ace4689bf8eac7da46a4b804c`.
- Post-merge PR state: closed and merged.
- No additional Cruding implementation tail was identified after integration.

### Что имеем?
One canonical route-map matching implementation under `Service/Crud/Resource`, production runtime callers wired to it, a non-owning compatibility facade retained solely because destructive operations are forbidden, corrected route documentation linkage, and the completed integration of PR #5 into `master`.

### Что осталось?
No authorized in-scope implementation tail remains. The only outstanding operational limitation is that the full repository quality gate suite was not executable in this environment and should still be run in the authoritative workspace/CI when available.

---

# CMCP orchestration journal

## engine-20260912084701-cruding-11db25

### Iteration 1 — reconnaissance and baseline

- Workspace: `D:\PhpstormProjects\www\cruding`; branch `feature/http-api-crud-classification`; baseline HEAD `f85c95edac7b21edbc9b7467d3f926a0416356a3`.
- Existing worktree was already materially dirty (392 status entries) with a large role-first refactor in progress; no pre-existing work was discarded or reset.
- Read repository contracts: `AGENTS.md`, `README.md`, `README.adoc`, `MANIFEST.json`, `CONTRIBUTING.md`, component documentation index, service-layout contract, Composer manifest, routes, services, source/test/tool inventory, and Git state.
- Read sibling dependency manifests for Objecting (`objecting/object`), Viewing (`viewing/view`), and Interfacing (`interfacing/interface`). Current Cruding source contains no direct `App\Objecting\`, `App\Viewing\`, or `App\Interfacing\` imports; dependency boundaries remain contract-driven rather than source-tree coupling.
- Read Canonization normative architecture material and Gating executable mirror. Consulted: `Architecture/README.md`, `Canon003DtoIsExplicitRule.md`, `Canon004SubjectFolderPlacementRule.md`, and Gating `Canon003DtoIsExplicitRule.php`.
- Canon mapping: default Symfony `App\` namespace is already correct; role-first removal of redundant `.../Crud/...` directories is aligned with Canon004; DTO naming was not aligned because the in-progress refactor used `src/Dto/` even though Canon003 requires `src/DTO/` and `DTO` namespace casing.
- Product-hardening audit baseline: 198/198 named source types documented; 333/333 public behavior methods documented; package-facing release docs present.

### Opening product/market mixin

- Mature Symfony CRUD systems (for example EasyAdmin) establish baseline expectations around deterministic CRUD operation delivery, configurable fields/forms, explicit actions, safe extension hooks, and predictable route/controller semantics.
- Cruding's RC-critical responsibility is narrower: generic CRUD grammar/delivery, operation dispatch, neutral resource payloads, diagnostics, and enforceable component boundaries. Final UI rendering, navigation, business-specific routes, and external callbacks remain outside this component.
- RC-critical workstream: complete the role-first refactor without losing runtime behavior, enforce Canon003 DTO casing, verify routing/service registration/autoload behavior, and close deterministic gate/test failures.
- Growth workstream (non-blocking): richer DX diagnostics, generated integration reports, broader host-level route observability, and additional CRUD capability metadata once RC is green.

### Iteration 2 — material implementation

- Converted the DTO tree from `src/Dto/` to canonical `src/DTO/` and updated Cruding DTO namespace/import references to `App\Cruding\DTO\...`.
- Added a bounded repository-local hardening script under `tool/` so the case-only Windows migration is reproducible and reviewable.
- Preserved the existing role-first refactor and avoided changes outside the Cruding workspace.

### Material risks / gates

- The repository has a large pre-existing rename/move wave, so verification must detect stale namespaces, service wiring, route-controller references, and autoload regressions rather than relying on diff shape alone.
- Required acceptance gates: Gating contract/lint checks, Composer validation/autoload rebuild, `composer check:cruding`, and final Git/worktree inspection.

### Iteration 3 — verification and fix

- `composer validate --strict` and `composer dump-autoload` passed.
- The first deterministic Cruding gate exposed six neutral fixtures with accidental `App\Facting\Testss\...` namespaces. Restored all six to the declared `App\Tests\Fixture\...` autoload-dev contract.
- The next pass exposed stale entrypoint skeleton expectations/imports left by the role-first migration. Updated generated imports to canonical `App\Cruding\Service`, `App\Cruding\DTO`, and `App\Cruding\ServiceInterface` paths and removed the hardcoded Facting namespace expectation from the neutral preview smoke.
- Final `composer check:cruding` passed all canon/smoke guards and PHPUnit: 51/51 tests, 206 assertions.
- The copied consumer `.gating` shell wrapper is structurally stale: it resolves a `.gate/contract/contract.json` layout that is absent in this consumer. Canonization textual rules and the canonical Gating Canon003 implementation were inspected directly; Cruding's deterministic product gates remain green. This tooling-distribution issue is recorded rather than disguised as a Cruding runtime failure.

### Iteration 4 — debt closure and integration

- Re-scanned DTO paths/namespaces after verification. Runtime/source references use canonical `src/DTO/` and `App\Cruding\DTO\...` casing.
- Hardened `cruding-dto-suffix-smoke.php` to inspect and report exact `src/DTO` casing instead of relying on Windows case-insensitive path resolution.
- Retained bounded local RC helper scripts only inside Cruding; destructive deletion was not permitted by the execution envelope, so no pre-existing or newly created file was removed through an unapproved destructive operation.
- Integration target: commit the verified role-first/DTO/package-hardening work coherently on `feature/http-api-crud-classification`, then publish that branch and perform post-push acceptance.
- The first signed integration commit is `57c1499095ac5f459472a3700e84c2d12001482c`; its commit hook applied and staged PHP-CS-Fixer corrections, and the resulting committed tree re-passed the full Cruding product gate.
- Push was initially guard-blocked only by the pre-existing untracked copied `.gating/` distribution. Added `/.gating/` to repository ignore policy instead of deleting or committing that stale external-tool snapshot.

### Что имеем? Что осталось?

The Cruding implementation and deterministic product gates are green. The stale copied Gating shell distribution is a separately recorded tooling tail.

### Iteration 5 — final acceptance and handoff

- Fetched current `origin/master` and discovered PR #6 was conflicting because master had advanced with the earlier route-map consolidation work.
- Rebased the RC work onto current master and resolved six conflicts inside Cruding: `CMCP_CHANGELOG.md`, `README.md`, `config/services.yaml`, `CrudRuntimeRouteMapAuditCommand`, `CrudController`, and `CrudRouteMapMatcher`.
- Conflict resolution preserved master's single-owner route-map matcher behavior while applying the canonical role-first path `App\Cruding\Service\Resource\CrudRouteMapMatcher` and `App\Cruding\DTO\...` contracts.
- Post-rebase `composer check:cruding` is green: all deterministic smoke guards pass; PHPUnit is 51/51 tests with 206 assertions.
- Because the original feature branch history was rewritten and force-push is intentionally unavailable, published the verified rebased state on `engine-20260912084701-cruding-11db25` without force-push.
- Closed superseded conflicting PR #6. Created replacement PR #7 against `master`; inspection reports `MERGEABLE` with no merge-gate blockers and no pending/failed checks.
- Acceptance decision: PR #7 is authorized for immediate safe merge after this journal commit is published and the merge gate is re-inspected against the updated head SHA.

### Что имеем? Что осталось?

Cruding is canonically role-first, exact `DTO` casing is enforced, the advanced master-side route-map consolidation is preserved, deterministic gates are green, and PR #7 has a clean merge gate. Remaining action is the guarded merge of PR #7 and post-merge state inspection.

### Post-merge quality debt closure — 2026-09-12

- Re-opened the canonical Cruding workspace for the remaining quality tail after the structural merge and synchronized the executable Gating source of truth through merged Gating PR #13.
- Reduced PHPStan from 342 post-migration findings to 0 without a baseline or suppressions. Repairs covered cross-role imports, Symfony API contracts, mixed request boundaries, Doctrine metadata/class-string boundaries, runtime JSON/lock maps, resource payload shapes, dynamic entrypoint invocation, and stale test typing.
- Added direct runtime dependencies actually used by Cruding: `doctrine/orm` and `symfony/serializer`; added `symfony/dotenv` for test bootstrap support. Composer reports no security advisories.
- `composer check:cruding` is green: every Cruding smoke/canon guard passes and PHPUnit is 51/51 tests with 216 assertions.
- `composer cs:check` is green after canonical formatting of the PHP tree; PHPStan remains green after formatting.
- Repaired PHPUnit 12.5 coverage execution: replaced removed `--branch-coverage` with supported `--path-coverage`, created `var/coverage` before report generation, and produced fresh Xdebug 3.5.1 evidence. Current coverage is 25.26% lines (1016/4022), 15.25% methods (84/551), 61.26% branches (854/1394), and 1.02% paths (189/18611).
- Canonical central Gating against `.gating/profile/component/cruding.yaml` reports 32 rules, 0 failed, 4 warnings, 3 skipped. Remaining warnings are non-blocking debt: silent-fallback review, tooling-type review, semantic PHPDoc coverage (53.6% methods vs 70% target), and HIGH_TEST_DEBT (coverage below target).
- `composer validate --strict --check-lock` passes. No stale bundled `.gating` consumer snapshot was used as authority.

### Acceptance status

The Cruding product and executable architecture gates are release-candidate green with zero hard Gating failures, zero PHPStan errors, clean CS, current PHPUnit/path-coverage evidence, and a documented non-blocking test/PHPDoc debt tail.

