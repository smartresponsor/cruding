<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$routes = readFileStrict($root.'/config/routes/cruding_crud.yaml');
$apiRoutes = readFileStrict($root.'/config/routes/cruding_api_crud.yaml');
$routeIndex = readFileStrict($root.'/config/routes.yaml');
$resolver = readFileStrict($root.'/src/Service/Crud/CrudTokenizedRouteIntentResolver.php');
$controller = readFileStrict($root.'/src/Controller/Crud/CrudController.php');
$apiController = readFileStrict($root.'/src/Controller/Api/Crud/CrudApiController.php');
$tokenNormalizer = readFileStrict($root.'/src/Service/Crud/CrudRouteTokenNormalizer.php');
$intent = readFileStrict($root.'/src/Dto/Crud/CrudTokenizedRouteIntent.php');

assert(str_contains($routes, 'cruding_tokenized_catch_all:'), 'Missing tokenized CRUD catch-all route.');
assert(str_contains($routes, 'path: /{crudPath}'), 'CRUD route must capture raw path for PHP token resolver.');
assert(
    str_contains($routes, '/(?:index|new|create|import|bulk|show|read|page|edit|update|archive|restore|duplicate|delete|verify|pay)')
        && str_contains($routes, '/(?:show|read|page|edit|update|archive|restore|duplicate|delete|verify|pay)/')
        && str_contains($routes, '[a-z0-9][a-z0-9_-]{17,}'),
    'Browser CRUD route must support implicit authenticated identity and explicit id/slug member signatures.',
);
assert(!str_contains($routes, '%cruding.resource_requirement%'), 'Browser CRUD routing must not depend on the resource allowlist.');
assert(!str_contains($routes, "crudPath: '.+'"), 'CRUD route must not expose an unbounded global catch-all.');
assert(!str_contains($routes, 'resourcePath:'), 'CRUD route YAML must not contain semantic resourcePath requirements.');
assert(!str_contains($routes, 'operationToken:'), 'CRUD route YAML must not contain semantic operationToken requirements.');
assert(!str_contains($routes, 'slug:'), 'CRUD route YAML must not contain semantic slug requirements.');
assert(!str_contains($routes, '_crud_operation:'), 'CRUD route YAML must not hardcode operation decisions.');
assert(str_contains($routes, 'App\\Cruding\\Controller\\Crud\\CrudController'), 'CRUD catch-all must dispatch into tokenized controller.');

assert(str_contains($apiRoutes, 'cruding_api_read:'), 'Missing GET API CRUD route.');
assert(str_contains($apiRoutes, 'cruding_api_create:'), 'Missing POST API CRUD route.');
assert(str_contains($apiRoutes, 'cruding_api_member_mutation:'), 'Missing member mutation API CRUD route.');
assert(str_contains($apiRoutes, 'methods: [GET]'), 'GET API route must be method-specific.');
assert(str_contains($apiRoutes, 'methods: [POST]'), 'POST API route must be method-specific.');
assert(str_contains($apiRoutes, 'methods: [PUT, PATCH, DELETE]'), 'Mutation API route must be member-only.');
assert(str_contains($apiRoutes, '[a-z0-9][a-z0-9_-]{17,}'), 'API identity grammar must enforce minimum slug length.');
assert(!str_contains($apiRoutes, "crudPath: '.+'"), 'API route must not expose an unbounded global catch-all.');
assert(!str_contains($apiRoutes, 'resourcePath:'), 'API route YAML must not contain semantic resourcePath requirements.');
assert(strpos($routeIndex, 'cruding_api_crud:') < strpos($routeIndex, 'cruding_crud:'), 'API catch-all must be imported before generic CRUD catch-all.');
assert(strpos($routeIndex, 'cruding_crud:') < strpos($routeIndex, 'cruding_resource:'), 'Tokenized CRUD catch-all must be imported before legacy resource fallback routes.');

foreach (['resolveWeb', 'resolveApi', 'resolveTokens', 'consumeActorScope', 'ACTOR_SCOPE_MY', 'operationTokens', 'isIdentityToken', 'identifierField', 'viewFor'] as $needle) {
    assert(str_contains($resolver, $needle), sprintf('Tokenized resolver must expose %s.', $needle));
}

foreach ([
    'public const HTTP_COLLECTION_OPERATIONS',
    'public const HTTP_MEMBER_OPERATIONS',
    "if (isset(\$operationTokens[\$last]))",
    "if (null !== \$beforeLast && isset(\$operationTokens[\$beforeLast]))",
    'return null;',
] as $needle) {
    assert(str_contains($resolver, $needle), sprintf('Tokenized resolver missing grammar decision: %s.', $needle));
}

assert(!str_contains($resolver, "\$identity = \$last;\n        \$resourceTokens = array_slice(\$tokens, 0, -1);"), 'Resolver must not infer show from an unmarked trailing identity token.');

foreach (['CrudIndexOperationInterface', 'CrudShowOperationInterface', 'CrudCreateOperationInterface', 'CrudEditOperationInterface', 'CrudDeleteOperationInterface', 'runEntrypointOnly', 'applyIntent'] as $needle) {
    assert(str_contains($controller, $needle), sprintf('Tokenized controller must contain %s.', $needle));
}

foreach (['CrudApiIndexOperationInterface', 'CrudApiShowOperationInterface', 'CrudApiCreateOperationInterface', 'CrudApiUpdateOperationInterface', 'CrudApiDeleteOperationInterface', 'resolveApi'] as $needle) {
    assert(str_contains($apiController, $needle), sprintf('Tokenized API controller must contain %s.', $needle));
}

assert(str_contains($tokenNormalizer, 'explode'), 'Route token normalizer must split URI into tokens.');
assert(str_contains($intent, 'actorScope'), 'Tokenized intent must expose actor scope diagnostics.');
assert(str_contains($intent, 'isMyScoped'), 'Tokenized intent must expose my-scope helper.');
assert(str_contains($intent, 'diagnostics'), 'Tokenized intent must expose diagnostics.');

fwrite(STDOUT, "PASS: Symfony routes are resource-bounded structural catch-alls and Cruding tokenized resolver owns explicit semantic grammar.\n");

function readFileStrict(string $path): string
{
    $content = file_get_contents($path);
    assert(false !== $content, sprintf('Unable to read %s.', $path));

    return $content;
}
