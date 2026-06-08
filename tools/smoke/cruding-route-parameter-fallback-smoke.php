<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$servicesPath = $root.'/config/services.yaml';
$routes = [
    $root.'/config/routes/cruding_crud.yaml',
    $root.'/config/routes/cruding_api_crud.yaml',
    $root.'/config/routes/cruding_surface.yaml',
];

$services = file_get_contents($servicesPath);
if (false === $services) {
    fwrite(STDERR, "Cannot read config/services.yaml\n");
    exit(1);
}

$requiredParameters = [
    'cruding.resource_requirement',
    'cruding.resource_path_requirement',
    'cruding.surface_token_requirement',
    'cruding.identity_slug_requirement',
];

foreach ($requiredParameters as $parameter) {
    if (!str_contains($services, $parameter.':')) {
        fwrite(STDERR, sprintf("Missing route-loader fallback parameter: %s\n", $parameter));
        exit(1);
    }
}

$referencedParameters = [];
foreach ($routes as $routePath) {
    $routeConfig = file_get_contents($routePath);
    if (false === $routeConfig) {
        fwrite(STDERR, sprintf("Cannot read route config: %s\n", $routePath));
        exit(1);
    }

    if (preg_match_all('/%([^%]+)%/', $routeConfig, $matches)) {
        foreach ($matches[1] as $parameter) {
            $referencedParameters[$parameter] = $parameter;
        }
    }
}

foreach (array_values($referencedParameters) as $parameter) {
    if (!str_contains($services, $parameter.':')) {
        fwrite(STDERR, sprintf("Route references parameter without config/services.yaml fallback: %s\n", $parameter));
        exit(1);
    }
}

foreach (['show', 'index', 'new', 'import', 'export'] as $reservedToken) {
    if (!preg_match('/cruding\.identity_slug_requirement: .*'.preg_quote($reservedToken, '/').'/m', $services)) {
        fwrite(STDERR, sprintf("Identity slug fallback does not block reserved token: %s\n", $reservedToken));
        exit(1);
    }
}

fwrite(STDOUT, "PASS: Cruding route parameters have config/services.yaml fallbacks.\n");
