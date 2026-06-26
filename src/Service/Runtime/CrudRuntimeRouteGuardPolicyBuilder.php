<?php

declare(strict_types=1);

namespace App\Cruding\Service\Runtime;

use App\Cruding\Dto\Runtime\CrudRuntimeRouteGuardPolicy;

/**
 * Builds route-level requirements from runtime scope/entity/view token inputs.
 */
final class CrudRuntimeRouteGuardPolicyBuilder
{
    /**
     * @param list<string> $defaultReservedRootTokens
     * @param list<string> $defaultviewTokens
     * @param list<string> $defaultOperationTokens
     * @param list<string> $defaultResourcePathReservedTokens
     */
    public function __construct(
        private readonly CrudRuntimeTokenNormalizer $normalizer,
        private readonly array $defaultReservedRootTokens = [],
        private readonly array $defaultviewTokens = [],
        private readonly array $defaultOperationTokens = [],
        private readonly array $defaultResourcePathReservedTokens = [],
    ) {
    }

    /**
     * @param list<string> $configuredReservedTokens
     * @param list<string> $configuredviewTokens
     * @param list<string> $configuredOperationTokens
     * @param list<string> $configuredResourcePathReservedTokens
     */
    public function build(
        string $scopeRaw,
        string $entityRaw,
        string $viewTokenRaw,
        string $reservedRaw,
        array $configuredReservedTokens = [],
        array $configuredviewTokens = [],
        array $configuredOperationTokens = [],
        array $configuredResourcePathReservedTokens = [],
    ): CrudRuntimeRouteGuardPolicy {
        $scopeTokens = $this->normalizer->csvToTokenList($scopeRaw);
        $entityTokens = $this->normalizer->csvToTokenList($entityRaw);
        $runtimeviewTokens = $this->normalizer->csvToTokenList($viewTokenRaw);
        $runtimeReservedTokens = $this->normalizer->csvToTokenList($reservedRaw);

        $viewTokens = $this->mergeTokenLists($this->defaultviewTokens, $configuredviewTokens, $runtimeviewTokens);
        $operationTokens = $this->mergeTokenLists($this->defaultOperationTokens, $configuredOperationTokens);
        $resourcePathReservedTokens = $this->mergeTokenLists($this->defaultResourcePathReservedTokens, $configuredResourcePathReservedTokens);
        $reservedRootTokens = $this->mergeTokenLists(
            $this->defaultReservedRootTokens,
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
        $viewTokenRequirement = $this->normalizer->alternationRequirement($viewTokens);
        $identitySlugRequirement = $this->identitySlugRequirement($viewTokens, $operationTokens);
        $resourcePathRequirement = $this->resourcePathRequirement($resourceRequirement, $viewTokens, $operationTokens, $resourcePathReservedTokens);

        return new CrudRuntimeRouteGuardPolicy(
            scopeTokens: $scopeTokens,
            entityTokens: $entityTokens,
            viewTokens: $viewTokens,
            reservedRootTokens: $reservedRootTokens,
            operationTokens: $operationTokens,
            resourcePathReservedTokens: $resourcePathReservedTokens,
            allowedResourceTokens: $allowedResourceTokens,
            conflictingEntityTokens: array_values($conflicts),
            resourceRequirement: $resourceRequirement,
            resourcePathRequirement: $resourcePathRequirement,
            viewTokenRequirement: $viewTokenRequirement,
            identitySlugRequirement: $identitySlugRequirement,
        );
    }

    /**
     * @param list<string> $viewTokens
     * @param list<string> $operationTokens
     * @param list<string> $resourcePathReservedTokens
     */
    private function resourcePathRequirement(
        string $resourceRequirement,
        array $viewTokens,
        array $operationTokens,
        array $resourcePathReservedTokens,
    ): string {
        $reservedTokens = $this->mergeTokenLists($viewTokens, $operationTokens, $resourcePathReservedTokens);

        if ([] === $reservedTokens) {
            return sprintf('%s(?:/[a-z0-9][a-z0-9_-]*)*', $resourceRequirement);
        }

        $reservedRequirement = $this->normalizer->alternationRequirement($reservedTokens);

        return sprintf(
            '%s(?:/(?!(?:%s)$)[a-z0-9][a-z0-9_-]*)*',
            $resourceRequirement,
            $reservedRequirement,
        );
    }

    /**
     * @param list<string> $viewTokens
     * @param list<string> $operationTokens
     */
    private function identitySlugRequirement(array $viewTokens, array $operationTokens): string
    {
        $reservedTokens = $this->mergeTokenLists($viewTokens, $operationTokens);
        if ([] === $reservedTokens) {
            return '[A-Za-z0-9][A-Za-z0-9_-]*';
        }

        $reservedRequirement = $this->normalizer->alternationRequirement($reservedTokens);

        return sprintf('(?!%s$)[A-Za-z0-9][A-Za-z0-9_-]*', $reservedRequirement);
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
