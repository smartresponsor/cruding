<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$webRoutes = file_get_contents($root.'/config/routes/cruding_crud.yaml');
$apiRoutes = file_get_contents($root.'/config/routes/cruding_api_crud.yaml');
if (false === $webRoutes || false === $apiRoutes) {
    fwrite(STDERR, "Unable to read Cruding route configuration.\n");
    exit(1);
}

if (!str_contains($webRoutes, '/(?:index|new|create|import|bulk|show|read|page|edit|update|archive|restore|duplicate|delete|verify|pay)')
    || !str_contains($webRoutes, '/(?:show|read|page|edit|update|archive|restore|duplicate|delete|verify|pay)/')
    || !str_contains($webRoutes, '[a-z0-9][a-z0-9_-]{17,}')) {
    fwrite(STDERR, "Browser CRUD route must support implicit authenticated identity and explicit id/slug member signatures.\n");
    exit(1);
}

if (str_contains($webRoutes, '%cruding.resource_requirement%')) {
    fwrite(STDERR, "Browser CRUD route still depends on the resource allowlist.\n");
    exit(1);
}

if (!str_contains($apiRoutes, 'methods: [GET]')
    || !str_contains($apiRoutes, 'methods: [POST]')
    || !str_contains($apiRoutes, 'methods: [PUT, PATCH, DELETE]')
    || !str_contains($apiRoutes, '[a-z0-9][a-z0-9_-]{17,}')) {
    fwrite(STDERR, "API CRUD routes must classify collection and member shapes by HTTP method.\n");
    exit(1);
}

if (str_contains($webRoutes.$apiRoutes, "crudPath: '.+'")) {
    fwrite(STDERR, "Legacy global catch-all remains in Cruding routes.\n");
    exit(1);
}

fwrite(STDOUT, "Cruding catch-all resource boundary smoke passed.\n");
