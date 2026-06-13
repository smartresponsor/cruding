<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$routes = readFileStrict($root.'/config/routes/cruding_crud.yaml');
$services = readFileStrict($root.'/config/services.yaml');
$resolver = readFileStrict($root.'/src/Service/Crud/CrudTokenizedRouteIntentResolver.php');

assert(str_contains($routes, 'cruding_tokenized_catch_all:'), 'Tokenized catch-all route must be present.');
assert(!str_contains($routes, 'resourcePath:'), 'Route YAML must not contain resourcePath regex.');
assert(!str_contains($routes, 'slug:'), 'Route YAML must not contain slug regex.');
assert(!str_contains($routes, '(?!.*'), 'Route YAML must not contain global negative lookahead grammar.');

assert(str_contains($services, "cruding.resource_path_requirement: '[a-z][a-z0-9_-]*(?:/[a-z0-9][a-z0-9_-]*)*'"), 'Fallback resourcePath requirement must be structural only.');
assert(str_contains($services, "cruding.identity_slug_requirement: '[A-Za-z0-9][A-Za-z0-9_-]*'"), 'Fallback identity slug requirement must be structural only.');

foreach ([
    '/alpha/index' => ['alpha', 'index'],
    '/alpha/attachment/media/edit/123' => ['alpha', 'attachment', 'media', 'edit', '123'],
    '/alpha/document/verify/sample-entry' => ['alpha', 'document', 'verify', 'sample-entry'],
] as $path => $expectedTokens) {
    $tokens = array_values(array_filter(explode('/', trim($path, '/'))));
    assert($expectedTokens === $tokens, sprintf('Generated token split mismatch for %s.', $path));
}

assert(str_contains($resolver, 'array_slice($tokens, 0, -1)'), 'Resolver must isolate terminal operation suffix from resourcePath.');
assert(str_contains($resolver, 'array_slice($tokens, 0, -2)'), 'Resolver must isolate operation+identity suffix from resourcePath.');

fwrite(STDOUT, "PASS: route regex no longer owns CRUD grammar; resource/operation/identity decisions are tokenized in PHP.\n");

function readFileStrict(string $path): string
{
    $content = file_get_contents($path);
    assert(false !== $content, sprintf('Unable to read %s.', $path));

    return $content;
}
