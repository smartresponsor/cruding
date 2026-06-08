<?php

declare(strict_types=1);

require_once __DIR__.'/../../src/Dto/Runtime/CrudRuntimeRouteGuardPolicy.php';
require_once __DIR__.'/../../src/Service/Runtime/CrudRuntimeTokenNormalizer.php';
require_once __DIR__.'/../../src/Service/Runtime/CrudRuntimeRouteGuardPolicyBuilder.php';
require_once __DIR__.'/../../src/Service/Crud/CrudReservedRouteTokenPolicy.php';

use App\Cruding\Service\Crud\CrudReservedRouteTokenPolicy;
use App\Cruding\Service\Runtime\CrudRuntimeRouteGuardPolicyBuilder;
use App\Cruding\Service\Runtime\CrudRuntimeTokenNormalizer;

$configPath = __DIR__.'/../../config/cruding_reserved_token.yaml';
$defaultReservedRootTokens = readParameterTokenList($configPath, 'cruding.reserved_route_token.root');
$defaultSurfaceTokens = readParameterTokenList($configPath, 'cruding.reserved_route_token.surface');
$defaultOperationTokens = readParameterTokenList($configPath, 'cruding.reserved_route_token.operation');
$defaultResourcePathReservedTokens = readParameterTokenList($configPath, 'cruding.reserved_route_token.resource_path_only');

$builder = new CrudRuntimeRouteGuardPolicyBuilder(
    normalizer: new CrudRuntimeTokenNormalizer(),
    defaultReservedRootTokens: $defaultReservedRootTokens,
    defaultSurfaceTokens: $defaultSurfaceTokens,
    defaultOperationTokens: $defaultOperationTokens,
    defaultResourcePathReservedTokens: $defaultResourcePathReservedTokens,
);
$policy = $builder->build(
    scopeRaw: 'cruding,viewing,interfacing,administering,accessing',
    entityRaw: 'vendor,attachment,media,product,category',
    surfaceTokenRaw: 'card,table,gallery',
    reservedRaw: '',
);

$resourcePathPattern = '#^'.$policy->resourcePathRequirement.'$#';
$slugPattern = '#^'.$policy->identitySlugRequirement.'$#';

$mustMatchResourcePaths = ['vendor', 'vendor/index', 'vendor/product'];
$mustRejectResourcePaths = ['vendor/show', 'vendor/new', 'vendor/import'];
$mustMatchSlugs = ['acme-inc', '123abc'];
$mustRejectSlugs = ['index', 'show', 'new', 'import'];

foreach ($mustMatchResourcePaths as $value) {
    assert(1 === preg_match($resourcePathPattern, $value), sprintf('Expected resourcePath "%s" to match.', $value));
}

foreach ($mustRejectResourcePaths as $value) {
    assert(0 === preg_match($resourcePathPattern, $value), sprintf('Expected resourcePath "%s" to be rejected.', $value));
}

foreach ($mustMatchSlugs as $value) {
    assert(1 === preg_match($slugPattern, $value), sprintf('Expected slug "%s" to match.', $value));
}

foreach ($mustRejectSlugs as $value) {
    assert(0 === preg_match($slugPattern, $value), sprintf('Expected slug "%s" to be rejected.', $value));
}

$runtimePolicy = new CrudReservedRouteTokenPolicy($policy->surfaceTokens, $policy->operationTokens);
assert('reserved_operation_token_not_routed' === $runtimePolicy->reasonForIdentityToken('index'));
assert('reserved_operation_token_not_routed' === $runtimePolicy->reasonForIdentityToken('show'));
assert('reserved_operation_token_not_routed' === $runtimePolicy->reasonForIdentityToken('import'));
assert(null === $runtimePolicy->reasonForIdentityToken('acme-inc'));

fwrite(STDOUT, "PASS: reserved identity token guard blocks surface/operation tokens from classic CRUD identity routes.\n");

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
