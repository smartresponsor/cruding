# Cruding 9 W01 Canonical Deploy Fix

Base archive: `Cruding(9).zip`.

Scope applied from requested items `1,2,4,5`:

1. Fixed Twig bundle path registration from `/template` to `/templates` in `CrudingExtension`.
2. Removed duplicate DI import collision by excluding `src/Service/Crud/` from the broad `App\Cruding\Service\` service import while preserving the dedicated `App\Cruding\Service\Crud\` import with required `bind` maps.
4. Split hidden `ResponseTitle` out of `CrudApiExceptionSubscriber` into `src/Value/Api/CrudApiResponseTitle.php`.
5. Canonicalized non-prefixed class/interface names:
   - `IdentifiableInterface` → `CrudIdentifiableInterface`
   - `SluggableInterface` → `CrudSluggableInterface`
   - `CapabilityMatch` → `CrudCapabilityMatch`
   - `CapabilityProfile` → `CrudCapabilityProfile`
   - `ApiExceptionSubscriber` → `CrudApiExceptionSubscriber`
   - `ApiProblemResponseFactory` → `CrudApiProblemResponseFactory`
   - `ResponseTitle` → `CrudApiResponseTitle`

Validation performed:

- `php -l` over all PHP files: passed.
- `php tools/cruding/interfacing-provider-rendering-guard.php`: passed.

Not included in this wave:

- Catch-all route safety redesign.
- SOLID decomposition of large surface/route services.
- Controller boundary redesign under Viewing.
