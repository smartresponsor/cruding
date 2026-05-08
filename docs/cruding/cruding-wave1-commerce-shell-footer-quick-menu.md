# Cruding wave1: commerce shell footer and quick menu

This wave makes the active Cruding CRUD shell visible as an e-commerce product surface.

## Boundary

`templates/base.html.twig` is the active shell for CRUD routes such as `/catalog/`.
Interfacing-owned shell templates do not render these routes directly.

## Changes

- Adds an HTML-only top quick menu for account, commerce, and system shortcuts.
- Replaces the minimal CRUD footer with a system-wide commerce footer.
- Keeps per-resource CRUD operations in the resource side panel.
- Adjusts generic body labels toward product/e-commerce language.

## Verification markers

- `data-cruding-shell-slot="quick-menu"`
- `data-cruding-shell-slot="commerce-footer"`
- `data-cruding-shell-contract="commerce-shell-wave1"`
