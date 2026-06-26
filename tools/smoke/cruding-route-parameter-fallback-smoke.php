<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$servicesPath = $root.'/config/services.yaml';
$routes = [
    $root.'/config/routes/cruding_crud.yaml',
    $root.'/config/routes/cruding_api_crud.yaml',
    $root.'/config/routes/cruding_resource.yaml',
];

$services = file_get_contents($servicesPath);
if (false === $services) {
    fwrite(STDERR, "Cannot read config/services.yaml\n");
    exit(1);
}

$requiredParameters = [
    'cruding.resource_requirement',
    'cruding.resource_path_requirement',
    'cruding.view_token_requirement',
    'cruding.operation_token_requirement',
    'cruding.identity_slug_requirement',
];

foreach ($requiredParameters as $parameter) {
    if (!str_contains($services, $parameter.':')) {
        fwrite(STDERR, sprintf("Missing config/services.yaml fallback parameter: %s\n", $parameter));
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

if (isset($referencedParameters['cruding.resource_path_requirement']) || isset($referencedParameters['cruding.identity_slug_requirement']) || isset($referencedParameters['cruding.operation_token_requirement'])) {
    fwrite(STDERR, "Semantic CRUD grammar parameters must not be referenced by tokenized CRUD/API routes.\n");
    exit(1);
}

foreach (['assign', 'approve', 'pay'] as $operationToken) {
    if (!preg_match('/cruding\.operation_token_requirement: .*'.preg_quote($operationToken, '/').'/m', $services)) {
        fwrite(STDERR, sprintf("Operation token fallback does not include configured token: %s\n", $operationToken));
        exit(1);
    }
}

fwrite(STDOUT, "PASS: Cruding parameters remain available as container fallbacks, but route YAML no longer uses semantic CRUD regex parameters.\n");
