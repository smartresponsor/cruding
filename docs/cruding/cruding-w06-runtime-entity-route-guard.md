# Cruding W06 Runtime Entity Route Guard

## Doctrine

Cruding has three independent runtime token classes:

```text
APP_RUNTIME_SCOPE          = active runtime components/packages
APP_RUNTIME_ENTITY         = business/entity first-segment URL resources owned by Cruding
APP_RUNTIME_VIEW_TOKEN  = View grammar tokens used inside resource-bound view routes
```

`APP_RUNTIME_SCOPE` is not a URL resource source. Tokens such as `cruding`, `viewing`, `interfacing`, `administering`, and `accessing` are reserved root tokens by default.

`APP_RUNTIME_ENTITY` is the only source for Cruding root business entrypoints. It is used at Symfony route-matching level through route requirements, not inside controllers after a catch-all has already matched.

## Example

```env
APP_RUNTIME_SCOPE="cruding,viewing,interfacing,administering,accessing"
APP_RUNTIME_ENTITY="alpha,attachment,media,beta,gamma"
APP_RUNTIME_VIEW_TOKEN="show,index,card,table,gallery"
```

Generated resource requirement:

```text
(?:alpha|attachment|media|beta|gamma)
```

Generated resource path requirement:

```text
(?!.*(?:^|/)(?:new|edit|delete|audit|visibility|attach|detach)(?:$|/))(?:alpha|attachment|media|beta|gamma)(?:/[a-z0-9][a-z0-9_-]*)*
```

The following paths may match Cruding when the entity list allows them:

```text
/alpha
/alpha/attachment/media/show/123
/product
/category
```

The following paths are protected from Cruding root catch-all interception:

```text
/admin
/login
/viewing
/interfacing
/administering
/accessing
/cruding
```

## Conflict rule

If the same token exists in both `APP_RUNTIME_SCOPE` and `APP_RUNTIME_ENTITY`, it is treated as a configuration error.

Example:

```env
APP_RUNTIME_SCOPE="cruding,viewing"
APP_RUNTIME_ENTITY="alpha,viewing"
```

The guard command fails because `viewing` is a reserved runtime component token and cannot also be a Cruding business root token.

## Command

```bash
php bin/console crud:runtime:route-guard
```

The command prints the computed policy and fails on conflicts.
