<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$resolver = readFileStrict($root.'/src/Service/Crud/CrudTokenizedRouteIntentResolver.php');
$routes = readFileStrict($root.'/config/routes/cruding_crud.yaml');
$operation = readFileStrict($root.'/src/Service/Crud/Operation/CrudIndexOperation.php');
$controller = readFileStrict($root.'/src/Controller/Crud/CrudTokenizedController.php');

assert(!str_contains($routes, 'cruding_index_named:'), 'Static /{resourcePath}/index route must be removed; index is resolved from tokens.');
assert(str_contains($routes, 'cruding_tokenized_catch_all:'), 'Tokenized catch-all must replace index-specific routes.');
assert(str_contains($resolver, "operation: 'index'"), 'Tokenized resolver must resolve single-token resources as index.');
assert(str_contains($resolver, 'if (isset($operationTokens[$last]))'), 'Tokenized resolver must classify the last token as operation candidate first.');
assert(str_contains($operation, '$this->contextResolver->tryResolve($request)'), 'Index operation must resolve the canonical CRUD context.');
assert(!str_contains($operation, "entityClass: ''"), 'Index operation must not manufacture an unresolved CRUD context.');
assert(!str_contains($operation, 'tryExplicitRouteEntrypoint'), 'Index operation must not maintain a parallel explicit-route shortcut.');
assert(str_contains($operation, '$this->entrypointDispatcher->tryRun($request, $context)'), 'Index entrypoint dispatch must use the resolved CRUD context.');
assert(str_contains($controller, "'index' => \$this->indexOperation->handle(\$request)"), 'Tokenized and legacy controllers must share CrudIndexOperation dispatch.');

foreach (['alpha', 'beta-item', 'gamma-entry'] as $resourcePath) {
    assert('index' !== $resourcePath, 'Generated resourcePath fixture must not be index.');
    $path = '/'.$resourcePath.'/index';
    assert(str_ends_with($path, '/index'), 'Generated path must put index in operation suffix position.');
}

fwrite(STDOUT, "PASS: /{resourcePath}/index uses tokenized grammar and one resolved-context operation path.\n");

function readFileStrict(string $path): string
{
    $content = file_get_contents($path);
    assert(false !== $content, sprintf('Unable to read %s.', $path));

    return $content;
}
