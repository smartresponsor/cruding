<?php

declare(strict_types=1);

namespace App\Cruding\Service\Runtime;

use App\Cruding\Dto\Runtime\CrudRuntimeRouteGuardPolicy;

/**
 * Builds route-level requirements from runtime scope/entity/surface token inputs.
 */
final class CrudRuntimeRouteGuardPolicyBuilder
{
    private const DEFAULT_RESERVED_ROOT_TOKENS = [
        'admin',
        'api',
        'assets',
        'dashboard',
        'debug',
        'health',
        'interfacing',
        'login',
        'logout',
        'metrics',
        'profile',
        'viewing',
        'accessing',
        'administering',
        'cruding',
    ];

    private const DEFAULT_SURFACE_TOKENS = [
        'show',
        'index',
        'card',
        'table',
        'gallery',
        'compact',
        'full',
        'detail',
        'list',
    ];

    public function __construct(
        private readonly CrudRuntimeTokenNormalizer $normalizer,
    ) {
    }

    /**
     * @param list<string> $configuredReservedTokens
     * @param list<string> $configuredSurfaceTokens
     */
    public function build(
        string $scopeRaw,
        string $entityRaw,
        string $surfaceTokenRaw,
        string $reservedRaw,
        array $configuredReservedTokens = [],
        array $configuredSurfaceTokens = [],
    ): CrudRuntimeRouteGuardPolicy {
        $scopeTokens = $this->normalizer->csvToTokenList($scopeRaw);
        $entityTokens = $this->normalizer->csvToTokenList($entityRaw);
        $runtimeSurfaceTokens = $this->normalizer->csvToTokenList($surfaceTokenRaw);
        $runtimeReservedTokens = $this->normalizer->csvToTokenList($reservedRaw);

        $surfaceTokens = $this->mergeTokenLists(self::DEFAULT_SURFACE_TOKENS, $configuredSurfaceTokens, $runtimeSurfaceTokens);
        $reservedRootTokens = $this->mergeTokenLists(
            self::DEFAULT_RESERVED_ROOT_TOKENS,
            $configuredReservedTokens,
            $runtimeReservedTokens,
            $scopeTokens,
            $this->componentLikeTokens($scopeTokens),
        );

        $reservedLookup = array_fill_keys($reservedRootTokens, true);
        $conflicts = [];
        $allowed = [];
        foreach ($entityTokens as $entityToken) {
            if (isset($reservedLookup[$entityToken])) {
                $conflicts[$entityToken] = $entityToken;
                continue;
            }

            $allowed[$entityToken] = $entityToken;
        }

        $allowedResourceTokens = array_values($allowed);
        $resourceRequirement = $this->normalizer->alternationRequirement($allowedResourceTokens);
        $surfaceTokenRequirement = $this->normalizer->alternationRequirement($surfaceTokens);
        $resourcePathRequirement = sprintf(
            '(?!.*(?:^|/)(?:new|edit|delete|audit|visibility|attach|detach)(?:$|/))%s(?:/[a-z0-9][a-z0-9_-]*)*',
            $resourceRequirement,
        );

        return new CrudRuntimeRouteGuardPolicy(
            scopeTokens: $scopeTokens,
            entityTokens: $entityTokens,
            surfaceTokens: $surfaceTokens,
            reservedRootTokens: $reservedRootTokens,
            allowedResourceTokens: $allowedResourceTokens,
            conflictingEntityTokens: array_values($conflicts),
            resourceRequirement: $resourceRequirement,
            resourcePathRequirement: $resourcePathRequirement,
            surfaceTokenRequirement: $surfaceTokenRequirement,
        );
    }

    /**
     * @param list<string> ...$tokenLists
     *
     * @return list<string>
     */
    private function mergeTokenLists(array ...$tokenLists): array
    {
        $merged = [];
        foreach ($tokenLists as $tokenList) {
            foreach ($this->normalizer->normalizeTokenList($tokenList) as $token) {
                $merged[$token] = $token;
            }
        }

        return array_values($merged);
    }

    /**
     * @param list<string> $tokens
     *
     * @return list<string>
     */
    private function componentLikeTokens(array $tokens): array
    {
        $componentTokens = [];
        foreach ($tokens as $token) {
            if (str_ends_with($token, 'ing')) {
                $componentTokens[$token] = $token;
            }
        }

        return array_values($componentTokens);
    }
}
