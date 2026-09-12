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

### Что имеем? Что осталось?

The Cruding implementation and deterministic product gates are green. Remaining work is Git integration and the iteration-5 post-integration acceptance check; the stale copied Gating shell distribution is a separately recorded tooling tail.

