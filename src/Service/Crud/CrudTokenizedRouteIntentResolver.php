<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudTokenizedRouteIntent;
use Symfony\Component\HttpFoundation\Request;

final readonly class CrudTokenizedRouteIntentResolver
{
    public const ROUTE_FAMILY_WEB = 'tokenized_crud';
    public const ROUTE_FAMILY_API = 'tokenized_api_crud';
    public const ACTOR_SCOPE_MY = 'my';

    /**
     * @var array<string, string>
     */
    private const SURFACE_BY_OPERATION = [
        'edit' => 'admin',
        'update' => 'admin',
        'delete' => 'admin',
        'import' => 'admin',
        'archive' => 'admin',
        'restore' => 'admin',
        'duplicate' => 'admin',
        'bulk' => 'admin',
    ];

    public function __construct(
        private CrudRouteTokenNormalizer $tokenNormalizer,
        private CrudReservedRouteTokenPolicy $reservedRouteTokenPolicy,
    ) {
    }

    public function resolveWeb(Request $request): ?CrudTokenizedRouteIntent
    {
        $tokens = $this->requestTokens($request, 'crudPath');
        if ([] === $tokens) {
            return null;
        }

        $scoped = $this->consumeActorScope($tokens);
        if ([] === $scoped['tokens']) {
            return null;
        }

        return $this->resolveTokens(
            tokens: $scoped['tokens'],
            routeFamily: self::ROUTE_FAMILY_WEB,
            defaultSurface: 'public',
            actorScope: $scoped['actorScope'],
        );
    }

    public function resolveApi(Request $request): ?CrudTokenizedRouteIntent
    {
        $tokens = $this->requestTokens($request, 'crudPath');
        if ([] === $tokens) {
            return null;
        }

        $scoped = $this->consumeActorScope($tokens);
        $tokens = $scoped['tokens'];
        $actorScope = $scoped['actorScope'];
        if ([] === $tokens) {
            return null;
        }

        $method = strtoupper($request->getMethod());
        $operation = match ($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => null,
        };

        if (null !== $operation) {
            if (1 === count($tokens)) {
                return new CrudTokenizedRouteIntent(
                    routeFamily: self::ROUTE_FAMILY_API,
                    resourcePath: implode('/', $tokens),
                    operation: $operation,
                    surface: 'public',
                    identifierField: null,
                    identifierValue: null,
                    tokens: $tokens,
                    actorScope: $actorScope,
                );
            }

            $identity = array_pop($tokens);

            return new CrudTokenizedRouteIntent(
                routeFamily: self::ROUTE_FAMILY_API,
                resourcePath: implode('/', $tokens),
                operation: $operation,
                surface: 'public',
                identifierField: $this->identifierField($identity),
                identifierValue: $identity,
                tokens: [...$tokens, $identity],
                actorScope: $actorScope,
            );
        }

        return $this->resolveTokens(
            tokens: $tokens,
            routeFamily: self::ROUTE_FAMILY_API,
            defaultSurface: 'public',
            actorScope: $actorScope,
        );
    }

    /**
     * @param list<string> $tokens
     */
    private function resolveTokens(array $tokens, string $routeFamily, string $defaultSurface, ?string $actorScope = null): ?CrudTokenizedRouteIntent
    {
        $operationTokens = array_flip($this->reservedRouteTokenPolicy->operationTokens());
        $count = count($tokens);

        if (1 === $count) {
            return new CrudTokenizedRouteIntent(
                routeFamily: $routeFamily,
                resourcePath: implode('/', $tokens),
                operation: 'index',
                surface: $defaultSurface,
                identifierField: null,
                identifierValue: null,
                tokens: $tokens,
                actorScope: $actorScope,
            );
        }

        $last = $tokens[$count - 1];
        $beforeLast = $tokens[$count - 2] ?? null;

        if (isset($operationTokens[$last])) {
            $resourceTokens = array_slice($tokens, 0, -1);
            $operation = $last;

            return new CrudTokenizedRouteIntent(
                routeFamily: $routeFamily,
                resourcePath: implode('/', $resourceTokens),
                operation: $operation,
                surface: $this->surfaceFor($operation, null, $defaultSurface),
                identifierField: null,
                identifierValue: null,
                tokens: $tokens,
                actorScope: $actorScope,
            );
        }

        if (null !== $beforeLast && isset($operationTokens[$beforeLast])) {
            $resourceTokens = array_slice($tokens, 0, -2);
            $operation = $beforeLast;

            return new CrudTokenizedRouteIntent(
                routeFamily: $routeFamily,
                resourcePath: implode('/', $resourceTokens),
                operation: $operation,
                surface: $this->surfaceFor($operation, $last, $defaultSurface),
                identifierField: $this->identifierField($last),
                identifierValue: $last,
                tokens: $tokens,
                actorScope: $actorScope,
            );
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function requestTokens(Request $request, string $attribute): array
    {
        $value = $request->attributes->get($attribute);
        if (!is_scalar($value)) {
            $value = $request->getPathInfo();
        }

        return $this->tokenNormalizer->tokens((string) $value);
    }

    /**
     * @param list<string> $tokens
     *
     * @return array{tokens: list<string>, actorScope: ?string}
     */
    private function consumeActorScope(array $tokens): array
    {
        if ([] === $tokens) {
            return ['tokens' => [], 'actorScope' => null];
        }

        if (self::ACTOR_SCOPE_MY !== strtolower($tokens[0])) {
            return ['tokens' => $tokens, 'actorScope' => null];
        }

        return [
            'tokens' => array_values(array_slice($tokens, 1)),
            'actorScope' => self::ACTOR_SCOPE_MY,
        ];
    }

    private function identifierField(string $identity): string
    {
        return preg_match('/^\d+$/', $identity) ? 'id' : 'slug';
    }

    private function surfaceFor(string $operation, ?string $identity, string $defaultSurface): string
    {
        if (null !== $identity && !preg_match('/^\d+$/', $identity)) {
            return 'public';
        }

        return self::SURFACE_BY_OPERATION[$operation] ?? $defaultSurface;
    }
}
