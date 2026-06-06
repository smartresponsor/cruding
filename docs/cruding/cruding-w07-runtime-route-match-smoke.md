# Cruding W07 Runtime Route Match Smoke

W07 adds a host-application smoke layer for the W06 runtime route guard.

W06 introduced the canonical route protection model:

- `APP_RUNTIME_SCOPE` contains runtime components/packages and is reserved from Cruding root entity routes.
- `APP_RUNTIME_ENTITY` contains business/entity first-segment URL resources and drives Symfony route requirements.
- `APP_RUNTIME_SURFACE_TOKEN` contains surface grammar tokens and is separate from runtime scope and runtime entity tokens.
- reserved root tokens must not be captured by Cruding root catch-all routes.

W07 does not change route geometry, route prefixes, import order, or catch-all doctrine. It only makes the W06 policy testable through Symfony's router matcher.

## Host app smoke

Run from the Symfony host app root, not from the Cruding package repository:

```powershell
cd D:\PhpstormProjects\www\App

.\vendor\cruding\crud\tools\smoke\cruding-runtime-route-match-smoke.ps1
```

For a path repository checkout, run the copied script directly from the host app root.

The script sets process-level runtime env variables, clears the Symfony cache, runs the W06 guard command, then runs the W07 matcher smoke command.

Default env values used by the script:

```env
APP_RUNTIME_SCOPE="cruding,viewing,interfacing,administering,accessing"
APP_RUNTIME_ENTITY="vendor,attachment,media,product,category"
APP_RUNTIME_SURFACE_TOKEN="show,index,card,table,gallery,compact,full,detail,list"
```

## Command

```bash
php bin/console crud:runtime:route-match-smoke
```

The command verifies that reserved roots do not match Cruding routes:

```text
/admin
/login
/logout
/profile
/dashboard
/viewing
/interfacing
/accessing
/administering
/cruding
/api/admin
/api/login
/api/viewing
/api/interfacing
```

It also verifies that declared entity roots match Cruding routes when `APP_RUNTIME_ENTITY` produces allowed resources:

```text
/{firstAllowedEntity}
/{firstAllowedEntity}/
/api/{firstAllowedEntity}
/api/{firstAllowedEntity}/
/{firstAllowedEntity}/attachment/media/{firstSurfaceToken}/123
```

## Empty entity mode

If `APP_RUNTIME_ENTITY` is empty, W06 intentionally produces fail-closed requirements:

```text
resource requirement: ( ?! ) without spaces
resource path requirement: ( ?! ) without spaces
```

In that mode W07 skips positive Cruding entity checks by default. To require configured entities, run:

```bash
php bin/console crud:runtime:route-match-smoke --fail-on-empty-entity
```

## Custom paths

Additional reserved paths:

```bash
php bin/console crud:runtime:route-match-smoke --reserved-path=/custom-admin
```

Additional paths that must match Cruding:

```bash
php bin/console crud:runtime:route-match-smoke --cruding-path=/vendor/attachment/media/show/123
```

Disable built-in paths:

```bash
php bin/console crud:runtime:route-match-smoke --skip-defaults --reserved-path=/admin --cruding-path=/vendor
```

## Expected doctrine

`APP_RUNTIME_ENTITY` is a route requirement source. It is not a late controller validation list.

The desired behavior is:

```text
/admin        => no Cruding match
/login        => no Cruding match
/viewing      => no Cruding match
/interfacing  => no Cruding match
/vendor       => Cruding match when vendor is in APP_RUNTIME_ENTITY
/vendor/attachment/media/show/123 => Cruding surface token match when vendor is allowed and show is a surface token
```
