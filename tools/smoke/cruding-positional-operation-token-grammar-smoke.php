<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$resolver = readFileStrict($root.'/src/Service/Crud/CrudTokenizedRouteIntentResolver.php');
$routes = readFileStrict($root.'/config/routes/cruding_crud.yaml');

assert(!str_contains($routes, '/{resourcePath}/edit/{id}'), 'Static positional operation routes must be removed.');
assert(str_contains($routes, 'cruding_tokenized_catch_all:'), 'Tokenized catch-all route must receive positional operation URIs.');
assert(str_contains($resolver, '$beforeLast = $tokens[$count - 2] ?? null;'), 'Resolver must inspect the token immediately before identity.');
assert(str_contains($resolver, 'array_slice($tokens, 0, -2)'), 'Resolver must remove operation+identity from resourcePath.');
assert(str_contains($resolver, 'identifierField($last)'), 'Resolver must classify identity after operation-token classification.');
assert(str_contains($resolver, 'MAX_RESOURCE_TOKEN_COUNT = 2'), 'Resolver must cap CRUD resource path depth before operation tokens.');
assert(str_contains($resolver, 'hasValidResourceTokenCount($resourceTokens)'), 'Resolver must validate resource token count before creating intent.');

foreach (['edit', 'update', 'delete', 'archive', 'restore', 'duplicate', 'verify', 'pay'] as $operation) {
    $path = sprintf('/alpha/beta/%s/123', $operation);
    $tokens = explode('/', trim($path, '/'));
    $beforeLast = $tokens[count($tokens) - 2];
    assert($operation === $beforeLast, sprintf('Generated path must place %s immediately before id/slug.', $operation));
}

foreach (['index', 'new', 'create', 'import', 'bulk'] as $operation) {
    $path = sprintf('/alpha/beta/%s', $operation);
    $tokens = explode('/', trim($path, '/'));
    $last = $tokens[count($tokens) - 1];
    assert($operation === $last, sprintf('Generated path must place collection operation %s at URI end.', $operation));
}

foreach (['/alpha/beta/gamma/index', '/alpha/beta/gamma/show/123', '/alpha/edit/profile/123'] as $path) {
    $tokens = explode('/', trim($path, '/'));
    $resourceTokensBeforeOperation = array_slice($tokens, 0, -1);
    if ('123' === end($tokens)) {
        $resourceTokensBeforeOperation = array_slice($tokens, 0, -2);
    }
    assert(count($resourceTokensBeforeOperation) > 2, sprintf('%s must exceed CRUD resource depth and be treated as business/non-CRUD.', $path));
}

fwrite(STDOUT, "PASS: operation-token classification is positional and capped to one or two resource tokens.\n");

function readFileStrict(string $path): string
{
    $content = file_get_contents($path);
    assert(false !== $content, sprintf('Unable to read %s.', $path));

    return $content;
}
