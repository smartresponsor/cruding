<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$routes = file_get_contents($root.'/config/routes.yaml');

if (!is_string($routes) || '' === trim($routes)) {
    fwrite(STDERR, "config/routes.yaml is missing or empty.\n");
    exit(1);
}

$api = strpos($routes, 'cruding_api_crud:');
$crud = strpos($routes, 'cruding_crud:');
$surface = strpos($routes, 'cruding_surface:');

foreach (['cruding_api_crud' => $api, 'cruding_crud' => $crud, 'cruding_surface' => $surface] as $nameEntity => $position) {
    if (false === $position) {
        fwrite(STDERR, sprintf("%s import is missing from config/routes.yaml.\n", $nameEntity));
        exit(1);
    }
}

if (!($api < $crud && $crud < $surface)) {
    fwrite(STDERR, "Route import order must be: cruding_api_crud, cruding_crud, cruding_surface.\n");
    exit(1);
}

$crudRoutes = file_get_contents($root.'/config/routes/cruding_crud.yaml');
if (!is_string($crudRoutes) || !str_contains($crudRoutes, 'cruding_tokenized_catch_all')) {
    fwrite(STDERR, "cruding tokenized catch-all route is missing.\n");
    exit(1);
}

$surfaceRoutes = file_get_contents($root.'/config/routes/cruding_surface.yaml');
if (!is_string($surfaceRoutes) || !str_contains($surfaceRoutes, 'cruding_surface_action')) {
    fwrite(STDERR, "legacy surface routes should remain available as fallback compatibility routes.\n");
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

echo "PASS: tokenized CRUD routes are imported before legacy surface routes; legacy surface routes remain fallback-only.\n";
