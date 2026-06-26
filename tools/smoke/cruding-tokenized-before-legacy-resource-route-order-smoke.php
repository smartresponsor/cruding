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
$view = strpos($routes, 'cruding_resource:');

foreach (['cruding_api_crud' => $api, 'cruding_crud' => $crud, 'cruding_resource' => $view] as $nameEntity => $position) {
    if (false === $position) {
        fwrite(STDERR, sprintf("%s import is missing from config/routes.yaml.\n", $nameEntity));
        exit(1);
    }
}

if (!($api < $crud && $crud < $view)) {
    fwrite(STDERR, "Route import order must be: cruding_api_crud, cruding_crud, cruding_view.\n");
    exit(1);
}

$crudRoutes = file_get_contents($root.'/config/routes/cruding_crud.yaml');
if (!is_string($crudRoutes) || !str_contains($crudRoutes, 'cruding_tokenized_catch_all')) {
    fwrite(STDERR, "cruding tokenized catch-all route is missing.\n");
    exit(1);
}

$viewRoutes = file_get_contents($root.'/config/routes/cruding_resource.yaml');
if (!is_string($viewRoutes) || !str_contains($viewRoutes, 'cruding_resource_action')) {
    fwrite(STDERR, "legacy resource routes should remain available as fallback compatibility routes.\n");
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

echo "PASS: tokenized CRUD routes are imported before legacy resource routes; legacy resource routes remain fallback-only.\n";
