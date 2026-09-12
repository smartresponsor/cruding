# Contributing to Cruding

Cruding is a Symfony component repository. Changes must preserve its component boundary, canonical namespace, typed role-first tree, route grammar, and executable guards.

## Canonical structure

- Source namespace: `App\\Cruding\\` -> `src/`.
- Component tests: `App\\Cruding\\Tests\\` -> `tests/`.
- Neutral fixtures may use `App\\Tests\\Fixture\\`.
- Technical role precedes feature semantics: for example `Service/Runtime`, `Service/Resource`, `Service/Operation`, `Controller/Api`.
- Do not reintroduce redundant component folders such as `Service/Crud`, `Resolver/Crud`, or `Controller/Crud`.
- DTO classes and files use the `DTO` suffix.
- Existing descriptive PHPDoc and array-shape/type contracts must be preserved and updated with behavior changes.

## Local workflow

```bash
composer install
composer validate --strict
composer dump-autoload
composer check:cruding
```

`composer check:cruding` is the primary repository gate and includes canon guards, smoke checks, PHPDoc coverage, and PHPUnit.

When changing PHP files, also run the configured static-analysis or formatting tools available in the local environment. CrudConfiguration is stored in `phpstan.neon`, `psalm.xml`, `rector.neon`, and `.php-cs-fixer.php`.

## Documentation

- Update `README.adoc` when the public component contract or integration surface changes.
- Update `README.md` when the repository landing page or quick-start changes.
- Update `docs/cruding/README.adoc` when documentation is added, renamed, or reclassified.
- Update `CHANGELOG.md` for user-visible behavior, configuration, namespace, route, or integration changes.
- Historical implementation records must not be presented as the current contract.

## Change discipline

Keep structural refactors, behavior changes, test repairs, and documentation/package hardening separable where practical. Do not leave backup files or generated artifacts in tracked source/tooling directories; Git is the history mechanism.

