# Changelog

All notable Cruding changes are recorded here. The repository currently develops on unreleased component branches, so changes remain under `Unreleased` until a release tag is cut.

## Unreleased

### Changed
- Preserved the canonical component namespace root `App\\Cruding\\`.
- Removed redundant `Crud/` directory tokens beneath technical role folders while retaining `Crud*` class names.
- Normalized service feature branches to `Service/Api`, `Service/Runtime`, `Service/Resource`, and `Service/Operation`.
- Normalized matching `ServiceInterface` branches where implementation contracts exist.
- Renamed DTO classes and files to the explicit `DTO` suffix convention.
- Consolidated duplicate runtime/API/route-map implementations into one canonical path per responsibility.
- Updated dependency-injection configuration, routes, tests, smoke guards, and component documentation for the canonical tree.

### Added
- Machine-enforced DTO suffix and service-layout guards.
- Machine-enforced PHPDoc coverage for named source types and public behavior methods.
- Repository manifest and product-packaging documentation.

### Fixed
- Stale tests that still constructed services with superseded constructor contracts.
