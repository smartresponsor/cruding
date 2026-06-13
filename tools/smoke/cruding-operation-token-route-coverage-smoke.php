<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$tokens = readFileStrict($root.'/config/cruding_reserved_token.yaml');
$routes = readFileStrict($root.'/config/routes/cruding_crud.yaml');
$resolver = readFileStrict($root.'/src/Service/Crud/CrudTokenizedRouteIntentResolver.php');

$configured = extractConfiguredOperations($tokens);
assert([] !== $configured, 'Configured CRUD operation token list must not be empty.');

assert(str_contains($routes, 'cruding_tokenized_catch_all:'), 'All operation tokens must be covered by the tokenized catch-all route.');
assert(!str_contains($routes, '_crud_operation:'), 'Operation coverage must not depend on route-level _crud_operation defaults.');
assert(!str_contains($routes, 'operationToken:'), 'Operation coverage must not depend on operationToken route regex.');
assert(str_contains($resolver, 'operationTokens()'), 'Resolver must consume config-backed operation token policy.');

foreach ($configured as $operation) {
    assert(preg_match('/^[a-z][a-z0-9_-]*$/', $operation) === 1, sprintf('Invalid operation token: %s.', $operation));
}

foreach (['index', 'show', 'new', 'create', 'edit', 'update', 'delete', 'bulk', 'import', 'export', 'archive', 'restore', 'duplicate', 'assign', 'verify', 'approve', 'pay'] as $required) {
    assert(in_array($required, $configured, true), sprintf('Required operation token missing: %s.', $required));
}

assert(str_contains($resolver, 'isset($operationTokens[$last])'), 'Terminal operation token coverage missing.');
assert(str_contains($resolver, 'isset($operationTokens[$beforeLast])'), 'Identity-bound operation token coverage missing.');

fwrite(STDOUT, sprintf(
    "PASS: %d configured CRUD operation tokens are covered by tokenized PHP grammar instead of route regex.\n",
    count($configured)
));

/**
 * @return list<string>
 */
function extractConfiguredOperations(string $tokens): array
{
    $values = [];
    $inOperationBlock = false;

    foreach (preg_split('/\R/', $tokens) ?: [] as $line) {
        if (preg_match('/^\s*cruding\.reserved_route_token\.operation:\s*$/', $line)) {
            $inOperationBlock = true;
            continue;
        }

        if (!$inOperationBlock) {
            continue;
        }

        if (preg_match('/^\s*cruding\.reserved_route_token\.[a-z_]+:\s*$/', $line)) {
            break;
        }

        if (preg_match('/^\s*-\s*(?<token>[a-z_]+)\s*$/', $line, $match)) {
            $values[] = $match['token'];
        }
    }

    return array_values(array_unique($values));
}

function readFileStrict(string $path): string
{
    $content = file_get_contents($path);
    assert(false !== $content, sprintf('Unable to read %s.', $path));

    return $content;
}
