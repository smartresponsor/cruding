
# CMCP_CHANGELOG

## 2026-09-20 — Cruding RC completion pass

- Reconnaissance read the current Cruding `AGENTS.md`, `README.md`, `README.adoc`, `composer.json`, `MANIFEST.json`, source/service wiring, tests, scripts, staged/unstaged Git state, and the current bulk-mutation implementation. Mandatory helper contracts were reviewed from Objecting, Viewing, Interfacing, Gating, and Canonization; Collectioning and Tabling runtime contracts were additionally inspected because the bulk implementation imports them directly.
- Market/competitor baseline: mature Symfony CRUD/admin systems expose dedicated batch/bulk actions rather than aliasing them to create/update entrypoints; API Platform similarly separates mutation processing from state provision. Enterprise practice additionally requires backend authorization and non-leaking error responses.
- Canonization textual rules consulted directly: Canon001 (technical-role-first), Canon002 (interface-tree mirroring), Canon003 (explicit DTO naming), Canon008 (Composer dependency integrity), Canon014 (executable orchestration responsibility), Canon019 (no alternative layer taxonomy), Canon021 (Cruding generic CRUD ownership), Canon040 (independent php-code-coverage line/method/branch thresholds), Canon043 (local dev-master path dependency identity), and Canon045 (root local dependency closure). `OWNER_GUARD_CONSOLIDATION.md` was also read because the pre-existing staged cleanup removes local architecture-policy guards; it explicitly classifies Cruding as mixing architecture assertions with component behavior and directs platform invariants to Canonization/Gating while behavior stays in tests. Gating mirrors for the applicable Canon rules were located in the current Gating rule registry/source, and the current Cruding Gating profile enables Canon001/002/003/008/014/019/021/040.
- Target-to-canon mapping: the new `Service/Operation`, `ServiceInterface/Operation`, `Resolver`, and `DTO` paths comply with Canon001/002/003; Cruding is the Canon021 owner so generic bulk mutation belongs here; direct Collectioning/Tabling imports are declared runtime dependencies under Canon008; Objecting/Viewing/Interfacing are application-contour dependencies but are not direct Cruding package dependencies because this bundle imports no production namespace from them; existing Collectioning/Tabling path repositories satisfy Canon043 and Canon045 for Cruding's actual local runtime closure.
- Baseline gates on the factual dirty tree were green before this pass: `composer check:cruding` 83 tests / 398 assertions, PHPStan 0 errors, PHP-CS-Fixer clean, Composer strict/check-lock valid, and changed-file PHP lint green.
- RC-critical hardening selected: keep the explicit bulk mutation boundary and remove implementation-detail leakage from bulk error responses. Unsupported-action/scope validation no longer echoes internal exception messages; partial row failures expose a stable `crud_bulk_mutation_failed` code plus batch position instead of raw exception text and PHP class names.
- Growth workstream kept separate: richer bulk observability/audit correlation, idempotency keys, asynchronous/job-backed bulk execution, progress reporting, cancellation, and very-large-scope UX remain post-RC capabilities rather than release blockers.
- Material risks: bulk mutation is intentionally per-object transactional rather than one unbounded transaction; handlers must make `continueOnFailure()` policy explicit, and host UI confirmation remains presentation responsibility rather than Cruding server authorization.
- Gates to re-run after hardening: changed PHP lint, `composer check:cruding`, PHPStan, CS check, strict Composer validation, Git diff/status, signed commits, publication, and post-integration branch state.

### Что имеем? Что осталось?

The explicit bulk mutation boundary is materially implemented and response-hardened. Post-hardening verification is green: `composer check:cruding` passes 83 tests / 404 assertions; PHPStan reports 0 errors; PHP-CS-Fixer reports 0 fixable files; strict Composer validation with lock checking passes; changed/untracked PHP lint is clean. Remaining RC work is Git integration: commit the pre-existing staged owner-guard cleanup separately from this bulk change, publish the branch, and inspect the remote merge gate.

## 2026-09-19 — explicit bulk mutation boundary

- Reconnaissance verified the local Cruding controller, lifecycle, access, object resolution, service wiring, and the existing staged Canon040 cleanup. The four pre-existing staged files were preserved and not used as bulk implementation targets.
- Replaced the legacy controller dispatch `bulk -> create` with an explicit `CrudBulkOperationInterface` / `CrudBulkOperation` boundary; `import -> create` is intentionally unchanged.
- Added host-provided named `CrudBulkMutationHandlerInterface` handlers through a tagged resolver. Backend permission is enforced independently of UI visibility, and per-object authorization is evaluated before mutation.
- Bulk scope resolution reuses Collectioning definitions/query/scoped reading and Tabling `TableDataScopeResolver`; Cruding does not introduce a second selected/filtering language. Selected keys therefore remain the canonical single-scalar identifier `in` constraint, while filtered/currentPage preserve Collectioning semantics.
- Mutation lifecycle is executed per object through the existing dispatcher. `continueOnFailure()` makes partial-batch behavior explicit; otherwise failures abort. Existing lifecycle `after()` remains inside the transaction, so no after-commit/realtime guarantee is claimed.
- Regression coverage covers selected, filtered, currentPage, operation-level denial, unsupported action, malformed selection, partial failure reporting, and controller dispatch no longer falling through create.
- Final local verification: `composer check:cruding` green with 83 tests / 398 assertions; PHPStan green with zero errors; PHP-CS-Fixer dry-run clean; Composer strict/check-lock valid; Composer audit reports no advisories; changed-file PHP lint green; Xdebug coverage run green. Repository coverage is 32.79% lines / 18.57% methods / 75.41% branches; `CrudBulkOperation` is 89.33% lines / 78.33% branches. Canon040 line/method debt remains a separate remediation track.
- Integration is intentionally not attempted from this worktree because four unrelated Canon040 cleanup files were already staged before this bulk wave. The available signed-commit tool commits the whole Git index after staging explicit paths, and Console MCP exposes no non-destructive unstage operation; committing now would conflate unrelated work.

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

### Post-merge quality debt closure тАФ 2026-09-12

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

## 2026-09-13 — repository implementation RC cycle

### Iteration 1 — reconnaissance and baseline

- Workspace: `D:\PhpstormProjects\www\cruding`; branch `tabling-action-boundary-refactor-v2`; baseline HEAD `86d93cb51a17504ba702a0260d112ea1332ed6be`; clean worktree and upstream in sync.
- Read target contracts: `AGENTS.md`, `README.md`, `README.adoc`, `composer.json`, `MANIFEST.json`, `CONTRIBUTING.md`, `SECURITY.md`, current CRUD route/resource/service docs, prior CMCP evidence, and current coverage summary.
- Mandatory dependency contour checked: Objecting owns entity/system-field primitives; Viewing owns final rendering; Interfacing owns shell/template assets. Cruding currently has no direct production imports from those components, so Canon008 does not justify inventing hard Composer coupling merely because sibling folders exist. Collectioning and Tabling are direct runtime dependencies and are declared as local path repositories.
- Canonization consulted directly: architecture authority model plus Canon008, Canon017, Canon021, Canon029, Canon031, Canon039, and Canon040. Gating remains the executable mirror.
- Target-to-canon mapping: Cruding owns generic CRUD as required by Canon021; its role-first `App\\Cruding\\` tree and explicit DTO contract remain canonical; quality tooling/coverage scripts are present; current persistent coverage is materially below Canon040 targets and is `HIGH_TEST_DEBT` by line/method coverage (25.26% lines, 15.25% methods; branch coverage 61.26%).
- Market baseline: mature Symfony CRUD systems expect deterministic route/action behavior, permissions, extension hooks, search/filter/pagination surfaces, and regression-tested customization points. Cruding keeps only generic route/operation/payload/diagnostic responsibility; final rendering, navigation, business actions, and Objecting field ownership remain outside this component.
- RC-critical workstream selected: reduce factual high-risk test debt around under-covered core CRUD runtime collaborators, starting with route parameter extraction and route/provider resolution surfaces that already execute heavily but lack direct method-level tests. Preserve behavior; do not expand component ownership.
- Growth workstream (non-blocking): richer host-facing diagnostics and generated integration reports after RC; no speculative UI or business behavior in Cruding.
- Material risks: accidentally testing implementation trivia instead of public behavior, perturbing route grammar, or masking coverage debt with count-based heuristics. Canon040 requires php-code-coverage evidence, not test-count proxies.
- Gates planned: targeted PHPUnit, `composer check:cruding`, PHPStan, CS check, fresh `test:coverage`, Canon/Gating inspection, Git diff/status, then coherent integration if green.

### Iteration 2 — material implementation

- Added `tests/Unit/Resource/CrudRouteSupportTest.php` as direct regression coverage for four route-boundary collaborators without changing production semantics.
- Coverage exercises public scalar route-parameter extraction, boolean/float normalization, private/non-scalar attribute exclusion, provider-key detail aliases and index fallbacks, operation-to-view mapping, and deterministic template candidate order.
- No Objecting, Viewing, Interfacing, navigation, business-operation, or rendering responsibility was pulled into Cruding.

### Iteration 3 — verification and fix

- Initial PHPUnit verification exposed an incomplete generated test file (`Unclosed '{'`); the truncated tail was repaired immediately and `php -l` then passed.
- A subsequent direct `composer test` retry hit a transient Console MCP HTTP 502, so verification continued through the repository's primary deterministic gate rather than treating the connector error as a product failure.
- `composer check:cruding` is green: all Cruding canon/smoke guards pass and PHPUnit reports 55/55 tests with 224 assertions.
- `composer phpstan` is green with zero errors across 224 analyzed files.
- Fresh `composer test:coverage` is green and produced current php-code-coverage evidence: 25.40% lines (1021/4020), 15.40% methods (85/552), and 63.86% branches (866/1356). This improves branch evidence from 61.26% while overall Canon040 `HIGH_TEST_DEBT` remains because line and method coverage are still below 50%.
- Route-support evidence improved materially: `CrudRouteProviderKeyResolver` now reaches 100% lines / 83.33% branches, `CrudRouteViewResolver` reaches 100% lines and branches, and `CrudRouteParameterExtractor` reaches 100% lines / 94.74% branches. php-code-coverage method/path semantics remain authoritative even where a fully executed small method is not classified as method-covered.

### Iteration 4 — debt closure and integration preparation

- `composer cs:check` identified formatting normalization in the new test plus `src/Builder/Resource/CrudResourceActionBuilder.php`; `composer cs:fix` normalized both. Git shows no semantic textual diff for the pre-existing source file, so no production behavior change was introduced there.
- Post-fix `composer cs:check` is green: 0/224 files require formatting changes.
- `composer validate --strict --check-lock` is green.
- Post-fix `composer check:cruding` was re-run and remains green at 55 tests / 224 assertions.
- Canonization textual rules and their actual Gating mirrors were both inspected for Canon008, Canon021, Canon031, Canon039, and Canon040; the implementation remains inside Cruding ownership and the remaining coverage debt is explicitly classified rather than hidden.

### Что имеем? Что осталось?

The selected RC-critical route-support hardening is implemented and verified, with stronger direct regression coverage and improved branch evidence. Overall repository coverage remains a documented Canon040 `HIGH_TEST_DEBT` warning; it is genuine follow-up remediation debt, not a hard failure of this bounded RC wave. Remaining work in this run is Git integration and post-push final acceptance.

### Iteration 5 — final acceptance and handoff

- Signed implementation commit: `ca11cfe59a1c619b37a2242c5e9d7efba8bbd236` (`test(cruding): harden route support coverage`).
- Push to `origin/tabling-action-boundary-refactor-v2` succeeded; post-push branch state is clean, ahead 0 / behind 0.
- The integration commit contains only `CMCP_CHANGELOG.md` and the new `tests/Unit/Resource/CrudRouteSupportTest.php`; the temporary formatting-only source working-tree change normalized away and is not part of the commit.
- Final accepted gate evidence for this bounded wave: `composer check:cruding` green (55 tests, 224 assertions), PHPStan 0 errors, CS check 0 fixable files, Composer strict/check-lock valid, and fresh coverage evidence generated successfully.
- Acceptance decision: the selected RC-critical route-support hardening is complete and published. No additional implementation tail is required to make this bounded wave factual; broader Canon040 coverage remediation remains explicit post-RC debt.

### Что имеем? Что осталось?

Cruding now has direct regression protection for route parameter extraction, provider-key fallback/alias ordering, operation-to-view mapping, and template-candidate generation, with all deterministic repository gates green and the verified branch published. What remains is broader test-development work to lift repository-wide line/method coverage out of `HIGH_TEST_DEBT`; that is a separate continuing remediation track rather than unfinished work in this RC slice.

### Iteration 6 — continuous RC coverage hardening

- Continued Canon040 remediation by risk rather than test-count inflation. The next wave targeted `CrudObjectFinder`, `CrudRuntimeRouteGuard`, and `CrudResourceContract` because they sit on persistence lookup, route-policy, and neutral payload boundaries.
- Expanded `CrudObjectFinderTest` with empty/unsupported entity handling, canonical `objectSlug` fallback, bounded pagination (`limit` capped at 500), offset calculation, and request timing diagnostics.
- Expanded runtime guard verification with root-token normalization, allow-list rejection, policy projection, and conflict signaling.
- Expanded resource-contract verification across template context, fallback payload, metadata, defaults, view modes, and slot-location fallback.
- Verification exposed a real Canon017 documentation/runtime mismatch: `CrudResourceContract::toTemplateContext()` promotes arbitrary producer metadata through `+ $meta`, while its prior PHPDoc described a sealed array shape. The return contract was corrected to truthful open `array<string, mixed>` documentation with meaningful descriptions; runtime behavior was not changed.
- PHPUnit 12 mock strictness also exposed one expectation-free metadata mock; it was replaced with a stub rather than suppressing the notice.
- Final deterministic gates are green: `composer check:cruding` reports 62/62 tests and 271 assertions; PHPStan reports 0 errors; `composer cs:check` reports 0/224 fixable files; fresh coverage execution passes under Xdebug/php-code-coverage.
- Coverage moved from the Iteration 5 baseline of 25.40% lines / 15.40% methods / 63.86% branches to 26.74% lines (1075/4020) / 15.40% methods (85/552) / 68.36% branches (927/1356). Branch coverage is now 1.64 percentage points below the Canon040 70% target.
- Focused class evidence: `CrudRuntimeRouteGuard` is 100% lines / 85.00% branches; `CrudResourceContract` is 98.72% lines / 84.62% branches; `CrudObjectFinder` improved to 46.38% lines / 36.36% branches.

### Что имеем? Что осталось?

This continuous RC wave materially improved branch and line coverage while also repairing a factual public-contract PHPDoc drift discovered by strict analysis. The next useful wave should prioritize `CrudObjectFinder` actor-owned branches and other high-line/low-method classes; repository-wide method coverage remains the dominant Canon040 `HIGH_TEST_DEBT` driver.

## 2026-09-14 — repository implementation RC cycle

### Reconnaissance and baseline

- Workspace: `D:\PhpstormProjects\www\cruding`; branch `tabling-action-boundary-refactor-v2`; baseline HEAD `a923a054a923441a3be99201ac5fee51eb68b661`; worktree clean and upstream synchronized.
- Read target contracts and implementation: root `AGENTS.md`, `README.md`, `README.adoc`, `composer.json`, `MANIFEST.json`, contribution/security/release documentation, current CRUD architecture documents, `CrudingBundle`, `CrudingExtension`, `CrudController`, existing CMCP journal, Composer scripts, and Git state.
- Mandatory dependency contour checked: Objecting owns reusable entity/system fields; Collectioning owns provider-neutral query semantics; Tabling owns backend table metadata/actions; Viewing owns final render decisions; Interfacing owns shell/templates. Cruding has no direct production imports from Objecting, Viewing, or Interfacing, so no unsupported direct runtime dependency is invented.
- Canonization textual rules consulted directly: Canon021 (Cruding generic CRUD ownership), Canon022 (standalone application dependency baseline), Canon043 (local development dependencies use exact `dev-master` plus path `options.versions`), and Canon045 (root local repository closure). Gating mirrors for Canon021/022/043/045 and the Cruding profile were inspected separately.
- Target-to-canon mapping: Canon021 is satisfied by Cruding ownership; Canon022 is applicable only to standalone Symfony boot surfaces and must not be used to force application-only dependencies into this pure bundle package; Canon043 applies because Cruding locally links Collectioning and Tabling; Canon045 applies to their reachable local path closure, with Collectioning already exposed at the root.
- Factual RC-critical defect: Cruding's development manifest still uses non-canonical `dev-main`/feature-branch alternatives for local first-party dependencies and omits `options.versions[package] = dev-master` on both path repositories. The README quick-start also still advertises `*@dev`. Current local Cruding gates do not catch this because the checked Cruding Gating profile predates Canon043/045 enablement.
- Market baseline: mature CRUD systems centralize generic CRUD orchestration while separating query, table/action metadata, and presentation responsibilities. Cruding's current boundary remains competitive and coherent; package determinism is the immediate RC concern rather than speculative feature expansion.
- RC-critical workstream: materialize Canon043 in Cruding's development Composer contract and package-facing installation example, verify dependency solving and the complete existing quality gate, then integrate the bounded change.
- Growth workstream: continue Canon040 coverage uplift and richer host-facing diagnostics after this packaging hardening; do not block this RC slice on speculative UI/DX growth.
- Material risks: Composer package identity changing with sibling feature branches, root repository-closure assumptions, and accidentally adding application-only Objecting/Viewing/Interfacing dependencies to a component that does not consume them.
- Gates planned: strict Composer validation, Composer dependency-resolution dry run, `composer check:cruding`, PHPStan, CS check, targeted Canon043/045 evidence, Git diff/status, signed commit, push, and post-push branch verification.

### Что имеем? Что осталось?

The runtime baseline is green (`composer check:cruding`: 62 tests / 271 assertions; PHPStan: 0 errors), and the remaining concrete RC defect is the stale local Composer package-version identity contract. Next: patch only `composer.json`, `README.md`, and this orchestration journal, then execute the full acceptance contour.

### Material implementation and verification

- Updated Cruding's direct local first-party constraints to exact `dev-master` for `collectioning/collection` and `tabling/table`.
- Added `options.versions` pins for both local path repositories so sibling feature-branch checkouts retain canonical `dev-master` Composer identity while `symlink: true` remains intact.
- Updated the README local installation example to use `dev-master` and a matching path-package version pin instead of the obsolete `*@dev` example.
- The first strict Composer validation correctly failed because the ignored local `composer.lock` still identified Tabling as `dev-backend-table-actions`; package-scoped Composer update synchronized the local resolution state. No tracked lock-file change was introduced.
- Composer resolution upgraded the local path identities to `collectioning/collection dev-master` and `tabling/table dev-master`; the update also refreshed ignored local lock entries for Doctrine ORM and Symfony YAML. Composer reported no security advisories.
- `composer validate --strict` now passes.
- `composer check:cruding` passes after the package change: 62 tests, 271 assertions, all repository canon/smoke guards green.
- `composer phpstan` passes with 0 errors across 224 analyzed files.
- `composer cs:check` passes with 0/224 fixable files.
- Canon043 evidence is satisfied directly in the resulting manifest: `minimum-stability=dev`, `prefer-stable=true`, exact `dev-master` direct constraints, `symlink=true`, and matching `options.versions` for each local first-party path repository.
- Canon045 closure remains satisfied for Cruding: both directly linked first-party repositories are visible from the root, and Tabling's local Collectioning dependency is already exposed by Cruding's root Collectioning repository. Tabling's own manifest policy is a sibling-repository concern and was not modified from the Cruding task.
- The canonical Gating implementation contains Canon043/Canon045 enforcement, but the currently inspected Cruding Gating profile does not yet enable those newer rules. No duplicate Cruding-local gate was invented because Gating owns executable canon enforcement.

### Что имеем? Что осталось?

Cruding's runtime and static-analysis baseline remains green, and its development Composer contract is now aligned with the current canonical first-party `dev-master` policy without adding application-only dependencies. Remaining work in this bounded RC slice is Git diff review, signed integration, push, and post-push state verification.

### Integration and acceptance

- Final pre-integration diff is bounded to `composer.json`, `README.md`, and `CMCP_CHANGELOG.md`; no runtime PHP source was changed.
- Initial signed implementation commit was `caaa916`; after fetching current `origin/master`, rebase skipped the already-applied table-action commit and replayed the package change as `5693c81` (`chore(cruding): canonicalize local package versions`).
- The ignored local Composer lock was synchronized for verification only and is not part of the tracked change set.
- Acceptance gates before publication remain green: strict Composer validation, full `composer check:cruding` (62 tests / 271 assertions), PHPStan (0 errors), and CS check (0/224 fixable files).

### Что имеем? Что осталось?

The bounded RC-critical package-version hardening is implemented, verified, and signed. Only branch publication and the final post-push clean/upstream-state inspection remain.

### PR conflict repair

- The original branch was pushed and PR #14 opened against `master`; GitHub correctly reported the PR as `CONFLICTING` because the branch still had pre-merge ancestry from the earlier Tabling action work.
- Fetched current `origin/master` (`0dc8264`, merged PR #13) before changing history; the first local rebase attempt had been against a stale remote-tracking ref and made no change.
- Rebased onto the refreshed `origin/master`. Git automatically skipped previously applied commit `86d93cb` and replayed the five genuinely new commits without content conflicts.
- Post-rebase strict Composer validation, `composer check:cruding` (62 tests / 271 assertions), and PHPStan (0 errors) remain green.
- PHP-CS-Fixer initially reported replay-related formatting normalization across the newer coverage files; `composer cs:fix` normalized five files, and `git diff` is empty afterward, proving no semantic/textual delta remained from that normalization pass.
- The already-published old branch will not be force-pushed. The rebased state must be published under a new branch, then PR #14 can be superseded safely.

### Что имеем? Что осталось?

The rebased Cruding tree now sits cleanly on current master with the already-merged Tabling action commit deduplicated, all semantic gates green, and no uncommitted formatter delta. Remaining work: commit this factual journal correction, publish the rebased head under a new branch, replace PR #14, inspect the new merge gate, and merge only if green.

### Final integration acceptance

- Published the rebased state as `cruding-rc-dev-master-hardening` without force-pushing the superseded branch.
- Replacement PR #15 passed the GitHub merge gate as `MERGEABLE` with zero blockers and no pending or failed checks, then squash-merged successfully into `master`.
- Superseded conflicting PR #14 was closed after #15 merged.
- Post-merge fetch advanced `origin/master` to `f873082`; the published integration branch was clean and synchronized with its upstream at head `0c2a0fb` before this journal-only closure.
- Final accepted technical evidence remains: strict Composer validation green; `composer check:cruding` 62/62 tests with 271 assertions; PHPStan 0 errors; CS check 0/224 fixable files; Canon043 development package identity materialized; Canon045 root local repository closure preserved.
- No runtime PHP semantics were changed by the package-policy slice. The broader Canon040 line/method coverage debt remains an explicit growth/remediation track rather than an RC blocker for this bounded workstream.

### Что имеем? Что осталось?

The RC-critical Cruding package-version hardening and the accumulated verified regression-coverage work are integrated into `master`. No authorized in-scope RC tail remains; subsequent work is the separate growth track for broader Canon040 coverage and host-facing diagnostics.

### Iteration 7 — Canon040 branch threshold closure

- Continued the explicit post-RC Canon040 remediation track on branch `cruding-rc-journal-close-v2`, starting from clean synchronized head `46cfe44c3f0e8a9f7284c89742556c96400dec5f`.
- Selected public runtime behavior rather than synthetic count inflation: implicit actor-owned lookup in `CrudObjectFinder` and lock discovery/normalization in `CrudRuntimeLockReader`.
- Added `CrudObjectFinder` regression scenarios for unauthenticated implicit page lookup diagnostics and authenticated owner-association fallback using a concrete Symfony user.
- Added `CrudRuntimeLockReader` regression scenarios for missing-lock state plus nested runtime payloads combining CSV token lists, array token lists, duplicate package names, normalization, and reserved/view/entity extraction.
- No Cruding production semantics or cross-component ownership boundaries were changed in this wave.
- Final gates are green: PHP lint on changed tests; `composer check:cruding` 66/66 tests with 291 assertions; PHPStan 0 errors; `composer cs:check` 0/224 fixable files; `composer validate --strict --check-lock` valid; Xdebug/php-code-coverage execution green.
- Repository coverage moved from 26.74% lines / 15.40% methods / 68.36% branches to 27.61% lines (1110/4020) / 15.76% methods (87/552) / 70.35% branches (954/1356). The Canon040 branch threshold of 70% is now satisfied.
- Focused improvements: `CrudObjectFinder` reached 73.91% lines / 62.12% branches; `CrudRuntimeLockReader` reached 50.00% methods / 90.12% lines / 83.56% branches.

### Что имеем? Что осталось?

Canon040 branch coverage is now above target and no longer contributes to the warning. The remaining `HIGH_TEST_DEBT` is driven by repository-wide line and especially method coverage; the next useful waves should prioritize public methods on high-line/low-method runtime classes rather than further optimizing branch percentage alone.

### Iteration 8 — method-coverage runtime wave

- Continued Canon040 remediation with explicit focus on method coverage rather than branch percentage, targeting `CrudRouteMapLoader` and `CrudRuntimeComposerInventoryReader` because both already had strong line execution but weak method classification.
- Added route-map loader scenarios for malformed-line rejection, nested inline values containing commas, non-YAML exclusion, extra metadata preservation, and a host with no local route directory.
- The first full gate exposed fixture contamination from an older `%TEMP%/Vendoring` sibling created by `testScansSiblingComponentRouteMaps`. This was a test-isolation defect, not production behavior: every route-map fixture is now nested under its own unique parent so central sibling scanning remains deterministic across the suite.
- Added composer-inventory scenarios covering `require`, `require-dev`, `replace`, and `provide`; installed package aggregation/deduplication; malformed lock entries; missing files; and invalid JSON.
- No production PHP source or Cruding ownership boundary changed in this wave.
- Final gates are green: changed-file PHP lint; `composer check:cruding` 70/70 tests with 310 assertions; PHPStan 0 errors; `composer cs:check` 0/224 fixable files; Xdebug/php-code-coverage run green.
- Coverage moved from 27.61% lines / 15.76% methods / 70.35% branches to 27.86% lines (1120/4020) / 15.94% methods (88/552) / 71.68% branches (972/1356).
- Focused evidence: `CrudRuntimeComposerInventoryReader` improved from 20% to 40% methods and now reaches 97.56% lines / 94.34% branches; `CrudRouteMapLoader` reaches 96.74% lines / 85.44% branches while php-code-coverage still classifies only 2/9 methods because its method score is path-completion-sensitive.

### Что имеем? Что осталось?

The method-focused wave produced a measurable method gain and improved test determinism without touching production semantics. The next high-value targets remain public runtime classes with multiple callable methods and high existing line coverage, such as `CrudResourceContractFactory`, `CrudPageDefinitionProvider`, `CrudCapabilityResolver`, and selected DTO/value-object behavior where tests can verify real contracts rather than constructors.

### Iteration 9 — capability and resource-contract method coverage

- Continued the Canon040 method-focused remediation track against `CrudCapabilityResolver`, `CrudCapabilityProfileDTO`, and `CrudResourceContractFactory`, selecting public contract behavior with strong existing line execution but low method classification.
- Expanded capability tests to cover `resolve()` with object capability detection, `supports()` with missing-class rejection, profile-level `supports()`/`match()` behavior, and unsupported capability fallback.
- Expanded resource-contract factory tests to cover route-operation override mapping, malformed workbench/location fallback, nested metadata sanitization, object-to-class conversion, resource-to-resource-type conversion, source-operation propagation, and location projection.
- The first PHPStan pass found only test-side mixed-offset access in the new contract assertions. Added explicit `assertIsArray()` narrowing and local variables instead of suppressions; PHPStan then returned to 0 errors.
- No production PHP source or component ownership boundary changed in this wave.
- Final gates are green: changed-file PHP lint; `composer check:cruding` 73/73 tests with 332 assertions before narrowing and 335 assertions in the fresh coverage run; PHPStan 0 errors; `composer cs:check` 0/224 fixable files; Xdebug/php-code-coverage green.
- Coverage moved from 27.86% lines / 15.94% methods / 71.68% branches to 28.28% lines (1137/4020) / 16.85% methods (93/552) / 73.16% branches (992/1356).
- Focused evidence: `CrudCapabilityResolver` now reaches 66.67% methods / 100% lines / 89.19% branches; `CrudCapabilityProfileDTO` reaches 100% methods/lines/branches; `CrudResourceContractFactory` reaches 50.00% methods / 97.62% lines / 93.33% branches.

### Что имеем? Что осталось?

This wave confirms that targeting callable contract behavior is materially more effective for Canon040 method debt than further branch-only hardening. The next useful targets are `CrudPageDefinitionProvider`, `CrudOwnershipDTO`/`CrudContextDTO` behavior, `CrudRuntimeDecisionGuard`, and other classes where multiple public methods remain under-classified despite substantial line execution.

### Iteration 10 — page-definition and DTO behavior coverage

- Continued Canon040 remediation against `CrudPageDefinitionProvider`, `CrudContextDTO`, and `CrudOwnershipDTO`, prioritizing a large line-debt runtime provider plus small behavioral DTO methods.
- Added collection-page coverage for `providePage()` without an object, verifying Collectioning-backed object/projected-row splitting, collection-page metadata, and no fallback repository call when collection data exists.
- Added detail-page coverage for `providePage()` with an object, including index/edit actions and identifier metadata.
- Added `provideNew()` / `provideEdit()` coverage for form-view propagation and delete action generation; the initial assertion incorrectly treated the fifth action-constructor argument as `style`, and was corrected to the actual DTO field `scope` after reading the contract.
- Added direct behavior coverage for `CrudContextDTO::isAdminView()` and every material branch of `CrudOwnershipDTO::canMutate()` (admin override, unsupported ownership, authenticated owner, unauthenticated owner).
- PHPStan found one nullable helper route parameter in the new test double; the helper now falls back to the context operation so it honors the interface return type without suppression.
- No production PHP source or component responsibility changed in this wave.
- Final gates are green: changed-file PHP lint; `composer check:cruding` 77/77 tests with 362 assertions; PHPStan 0 errors; `composer cs:check` 0/224 fixable files; Xdebug/php-code-coverage green.
- Coverage moved from 28.28% lines / 16.85% methods / 73.16% branches to 31.09% lines (1250/4020) / 17.57% methods (97/552) / 75.29% branches (1021/1356).
- Focused evidence: `CrudPageDefinitionProvider` now reaches 66.67% methods / 100% lines / 80.77% branches; `CrudContextDTO` and `CrudOwnershipDTO` now reach 100% methods, paths, branches, and lines.

### Что имеем? Что осталось?

This wave delivered the largest recent line-coverage gain while still improving method coverage, confirming that large public runtime providers are the highest-value next targets. Remaining `HIGH_TEST_DEBT` is still driven by repository-wide line and method coverage; the next wave should prioritize `CrudRuntimeDecisionGuard`, `CrudObjectFinder`, `CrudApiExceptionSubscriber`, and other public runtime classes with meaningful uncovered behavior.

