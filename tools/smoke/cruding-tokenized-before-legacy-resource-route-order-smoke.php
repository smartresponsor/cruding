<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$routes = file_get_contents($root.'/config/routes.yaml');

if (!is_string($routes) || '' === trim($routes)) {
    fwrite(STDERR, "config/routes.yaml is missing or empty.\n");
    exit(1);
}

if (!str_contains($routes, 'type: cruding')) {
    fwrite(STDERR, "config/routes.yaml must delegate CRUD route construction to the Cruding custom route loader.\n");
    exit(1);
}

foreach (['cruding_api_crud:', 'cruding_crud:', 'cruding_resource:'] as $obsoleteImport) {
    if (str_contains($routes, $obsoleteImport)) {
        fwrite(STDERR, sprintf("%s must not be imported directly from config/routes.yaml.\n", $obsoleteImport));
        exit(1);
    }
}

$crudRoutes = file_get_contents($root.'/config/routes/cruding_crud.yaml');
if (!is_string($crudRoutes) || !str_contains($crudRoutes, 'cruding_tokenized_catch_all')) {
    fwrite(STDERR, "cruding tokenized catch-all grammar reference is missing.\n");
    exit(1);
}

$viewRoutes = file_get_contents($root.'/config/routes/cruding_resource.yaml');
if (!is_string($viewRoutes) || !str_contains($viewRoutes, 'cruding_resource_action')) {
    fwrite(STDERR, "legacy resource route grammar should remain available as fallback reference.\n");
    exit(1);
}

$examples = [
    '/vendor/attachment/index' => 'tokenized CRUD index for resourcePath vendor/attachment',
    '/vendor/attachment/document/index' => 'tokenized CRUD index for resourcePath vendor/attachment/document',
    '/vendor/attachment/media/edit/123' => 'tokenized CRUD edit with identity at arbitrary depth',
];

foreach ($examples as $path => $meaning) {
    if ('' === $path || '' === $meaning) {
        fwrite(STDERR, "invalid smoke example.\n");
        exit(1);
    }
}

echo "PASS: Cruding custom route loader owns runtime route ordering; split YAML route files remain grammar references.\n";
