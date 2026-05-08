<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$failures = [];

function require_file_contains(string $path, string $needle, array &$failures): void
{
    if (!is_file($path)) {
        $failures[] = "Missing file: {$path}";
        return;
    }

    $contents = (string) file_get_contents($path);
    if (!str_contains($contents, $needle)) {
        $failures[] = "Missing expected marker in {$path}: {$needle}";
    }
}

$routes = $root . '/config/routes.yaml';
$routeFile = $root . '/config/routes/app_interfacing_assets.yaml';
$controller = $root . '/src/Controller/Interfacing/InterfacingAdminBodyAssetController.php';
$services = $root . '/config/services.yaml';

require_file_contains($routes, "routes/app_interfacing_assets.yaml", $failures);
require_file_contains($routeFile, '/interfacing/admin-body/{assetPath}', $failures);
require_file_contains($controller, 'BinaryFileResponse', $failures);
require_file_contains($controller, "public' . DIRECTORY_SEPARATOR . 'interfacing'", $failures);
require_file_contains($services, 'App\\Cruding\\Controller\\Interfacing\\', $failures);

$routesContents = is_file($routes) ? (string) file_get_contents($routes) : '';
$assetImportPosition = strpos($routesContents, "routes/app_interfacing_assets.yaml");
$crudImportPosition = strpos($routesContents, "routes/app_crud.yaml");
if (false === $assetImportPosition || false === $crudImportPosition || $assetImportPosition > $crudImportPosition) {
    $failures[] = 'Interfacing asset route import must be before app_crud catch-all routes.';
}

foreach ([
    '/public/interfacing/admin-body/provider-registry.js',
    '/public/interfacing/admin-body/canonical-providers.js',
    '/public/interfacing/admin-body/providers/antd-pro.js',
    '/public/interfacing/admin-body/providers/primereact.js',
    '/public/interfacing/admin-body/runtime.js',
    '/public/interfacing/admin-body/canonical-providers.interfacing-interface-ui.css',
] as $asset) {
    if (!is_file($root . $asset)) {
        $failures[] = "Missing published provider asset: {$asset}";
    }
}

if ([] !== $failures) {
    fwrite(STDERR, "Cruding Interfacing provider asset runtime route guard failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "Cruding Interfacing provider asset runtime route guard passed.\n";
