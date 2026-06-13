<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$resolver = readFileStrict($root.'/src/Service/Crud/CrudTokenizedRouteIntentResolver.php');
$routes = readFileStrict($root.'/config/routes/cruding_crud.yaml');

assert(!str_contains($routes, 'cruding_index_named:'), 'Static /{resourcePath}/index route must be removed; index is resolved from tokens.');
assert(str_contains($routes, 'cruding_tokenized_catch_all:'), 'Tokenized catch-all must replace index-specific routes.');
assert(str_contains($resolver, "operation: 'index'"), 'Tokenized resolver must resolve single-token resources as index.');
assert(str_contains($resolver, 'if (isset($operationTokens[$last]))'), 'Tokenized resolver must classify the last token as operation candidate first.');

foreach (['alpha', 'beta-item', 'gamma-entry'] as $resourcePath) {
    assert('index' !== $resourcePath, 'Generated resourcePath fixture must not be index.');
    $path = '/'.$resourcePath.'/index';
    assert(str_ends_with($path, '/index'), 'Generated path must put index in operation suffix position.');
}

fwrite(STDOUT, "PASS: /{resourcePath}/index is resolved by tokenized grammar, not a route regex or component-specific route.\n");

function readFileStrict(string $path): string
{
    $content = file_get_contents($path);
    assert(false !== $content, sprintf('Unable to read %s.', $path));

    return $content;
}
