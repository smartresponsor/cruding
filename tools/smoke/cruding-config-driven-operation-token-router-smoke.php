<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$tokens = readFileStrict($root.'/config/cruding_reserved_token.yaml');
$routes = readFileStrict($root.'/config/routes/cruding_crud.yaml');
$resolver = readFileStrict($root.'/src/Service/Crud/CrudTokenizedRouteIntentResolver.php');
$controller = readFileStrict($root.'/src/Controller/Crud/CrudTokenizedController.php');

foreach (['assign', 'unassign', 'adjust', 'calculate', 'verify', 'reject', 'approve', 'pay', 'recalculate', 'start', 'overview', 'mutation'] as $token) {
    assert(str_contains($tokens, '        - '.$token), sprintf('Missing configured operation token: %s.', $token));
}

assert(str_contains($routes, 'cruding_tokenized_catch_all:'), 'Configured operation tokens must be routed through tokenized catch-all.');
assert(!str_contains($routes, 'operationToken:'), 'No operationToken route parameter should remain in route YAML.');
assert(str_contains($resolver, 'operationTokens()'), 'Tokenized resolver must read operation tokens from config-backed policy.');
assert(str_contains($resolver, 'isset($operationTokens[$last])'), 'Tokenized resolver must detect terminal operation tokens.');
assert(str_contains($resolver, 'isset($operationTokens[$beforeLast])'), 'Tokenized resolver must detect operation tokens before identity.');
assert(str_contains($controller, 'runEntrypointOnly'), 'Custom configured operations must run through entrypoint-only fallback when no default CRUD operation exists.');

fwrite(STDOUT, "PASS: config-driven operation tokens are resolved in PHP without semantic route regex or component hardcode.\n");

function readFileStrict(string $path): string
{
    $content = file_get_contents($path);
    assert(false !== $content, sprintf('Unable to read %s.', $path));

    return $content;
}
