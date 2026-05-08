= Cruding Wave 47: Interfacing Provider Asset Publication

Wave 47 publishes the canonical Interfacing admin-body provider assets into the Cruding runtime public webroot for component-local development and host-runtime integration checks.

This is not a Cruding-owned UI implementation. Cruding remains the resource, object, form, and operation owner. Rendering remains delegated to `interfacing/bridge/provider_surface.html.twig`, and the browser renderer remains the Interfacing provider chain:

* `provider-registry.js`
* `canonical-providers.js`
* `providers/antd-pro.js`
* `providers/primereact.js`
* `runtime.js`
* `canonical-providers.interfacing-interface-ui.css`

The purpose is to make `/interfacing/admin-body/*` resolve inside the currently running webroot when Cruding is used as the development runtime. In the final host application, the same assets must be published by the host-level Interfacing integration, not reimplemented by consumer templates.

Forbidden as primary UI:

* Cruding handmade Twig CSS
* Bootstrap/EasyAdmin fallback UI
* `@Cruding/crud/*.html.twig` as final CRUD page renderer

Verification:

[source,powershell]
----
php tools/cruding/interfacing-provider-surface-rendering-guard.php
php tools/cruding/interfacing-provider-assets-publication-guard.php
php bin/console cache:clear
----

Browser network checks for `/vendor/`, `/category/`, `/catalog/` should show HTTP 200 for:

* `/interfacing/admin-body/provider-registry.js`
* `/interfacing/admin-body/canonical-providers.js`
* `/interfacing/admin-body/providers/antd-pro.js`
* `/interfacing/admin-body/providers/primereact.js`
* `/interfacing/admin-body/runtime.js`
