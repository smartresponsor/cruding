<?php

declare(strict_types=1);

require_once __DIR__.'/../../src/Service/Crud/CrudReservedRouteTokenPolicy.php';

use App\Cruding\Service\Crud\CrudReservedRouteTokenPolicy;

$configPath = __DIR__.'/../../config/cruding_reserved_token.yaml';
$surfaceTokens = readParameterTokenList($configPath, 'cruding.reserved_route_token.surface');
$operationTokens = readParameterTokenList($configPath, 'cruding.reserved_route_token.operation');

$runtimePolicy = new CrudReservedRouteTokenPolicy($surfaceTokens, $operationTokens);
foreach (['index', 'show', 'import', 'assign', 'approve', 'pay'] as $token) {
    assert('reserved_operation_token_not_routed' === $runtimePolicy->reasonForIdentityToken($token), sprintf('Expected operation token %s to be reserved as identity.', $token));
}

foreach (['card', 'table'] as $token) {
    assert('reserved_surface_token_not_routed' === $runtimePolicy->reasonForIdentityToken($token), sprintf('Expected surface token %s to be reserved as identity.', $token));
}

assert(null === $runtimePolicy->reasonForIdentityToken('acme-inc'));
assert(null === $runtimePolicy->reasonForIdentityToken('123abc'));

fwrite(STDOUT, "PASS: reserved identity token policy still blocks surface/operation tokens from identity classification after tokenized routing.\n");

/**
 * @return list<string>
 */
function readParameterTokenList(string $path, string $parameterName): array
{
    $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];
    $tokens = [];
    $inside = false;

    foreach ($lines as $line) {
        if (preg_match('/^\s{4}'.preg_quote($parameterName, '/').':\s*$/', $line)) {
            $inside = true;
            continue;
        }

        if (!$inside) {
            continue;
        }

        if (preg_match('/^\s{4}[A-Za-z0-9_.-]+:\s*$/', $line)) {
            break;
        }

        if (preg_match('/^\s{8}-\s+([A-Za-z0-9_-]+)\s*$/', $line, $match)) {
            $tokens[] = $match[1];
        }
    }

    return $tokens;
}
