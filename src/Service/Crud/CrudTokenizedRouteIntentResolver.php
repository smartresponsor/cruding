<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudTokenizedRouteIntent;
use Symfony\Component\HttpFoundation\Request;

final readonly class CrudTokenizedRouteIntentResolver
{
    public const ROUTE_FAMILY_WEB = 'tokenized_crud';
    public const ROUTE_FAMILY_API = 'tokenized_api_crud';

    private const CONTEXT_PREFIX_API = 'api';
    private const DEFAULT_BACKEND_CONTEXT_PREFIX = 'ea';
    private const MAX_RESOURCE_TOKEN_COUNT = 2;

    /** @var list<string> */
    public const HTTP_COLLECTION_OPERATIONS = [
        'index',
        'new',
        'create',
        'import',
        'bulk',
    ];

    /** @var list<string> */
    public const HTTP_MEMBER_OPERATIONS = [
        'show',
        'read',
        'page',
        'edit',
        'update',
        'archive',
        'restore',
        'duplicate',
        'delete',
        'verify',
        'pay',
    ];

    /**
     * @var array<string, string>
     */
    private const view_BY_OPERATION = [
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
        private ?string $backendContextToken = null,
    ) {
    }

    public function resolveWeb(Request $request): ?CrudTokenizedRouteIntent
    {
        $tokens = $this->requestTokens($request, 'crudPath');
        if ([] === $tokens) {
            return null;
        }

        return $this->resolveTokens(
            tokens: $tokens,
            routeFamily: self::ROUTE_FAMILY_WEB,
            defaultView: 'public',
        );
    }

    public function resolveApi(Request $request): ?CrudTokenizedRouteIntent
    {
        $tokens = $this->requestTokens($request, 'crudPath');
        if ([] === $tokens) {
            return null;
        }

        $method = strtoupper($request->getMethod());

        if ('GET' === $method) {
            $identity = $tokens[count($tokens) - 1];
            $resourceTokens = array_slice($tokens, 0, -1);

            if ($this->isIdentityToken($identity) && $this->hasValidResourceTokenCount($resourceTokens)) {
                return new CrudTokenizedRouteIntent(
                    routeFamily: self::ROUTE_FAMILY_API,
                    resourcePath: implode('/', $resourceTokens),
                    operation: 'show',
                    view: 'public',
                    identifierField: $this->identifierField($identity),
                    identifierValue: $identity,
                    tokens: $tokens,
                );
            }

            if (!$this->hasValidResourceTokenCount($tokens)) {
                return null;
            }

            return new CrudTokenizedRouteIntent(
                routeFamily: self::ROUTE_FAMILY_API,
                resourcePath: implode('/', $tokens),
                operation: 'index',
                view: 'public',
                identifierField: null,
                identifierValue: null,
                tokens: $tokens,
            );
        }

        if ('POST' === $method) {
            if (!$this->hasValidResourceTokenCount($tokens)) {
                return null;
            }

            return new CrudTokenizedRouteIntent(
                routeFamily: self::ROUTE_FAMILY_API,
                resourcePath: implode('/', $tokens),
                operation: 'create',
                view: 'public',
                identifierField: null,
                identifierValue: null,
                tokens: $tokens,
            );
        }

        if (!in_array($method, ['PUT', 'PATCH', 'DELETE'], true)) {
            return null;
        }

        $identity = array_pop($tokens);
        if (!$this->isIdentityToken($identity) || !$this->hasValidResourceTokenCount($tokens)) {
            return null;
        }

        return new CrudTokenizedRouteIntent(
            routeFamily: self::ROUTE_FAMILY_API,
            resourcePath: implode('/', $tokens),
            operation: 'DELETE' === $method ? 'delete' : 'update',
            view: 'public',
            identifierField: $this->identifierField($identity),
            identifierValue: $identity,
            tokens: [...$tokens, $identity],
        );
    }

    /**
     * @param list<string> $tokens
     */
    private function resolveTokens(array $tokens, string $routeFamily, string $defaultView): ?CrudTokenizedRouteIntent
    {
        $operationTokens = array_flip(array_values(array_unique(array_merge(
            $this->reservedRouteTokenPolicy->operationTokens(),
            self::HTTP_COLLECTION_OPERATIONS,
            self::HTTP_MEMBER_OPERATIONS,
        ))));
        $count = count($tokens);

        $last = $tokens[$count - 1];
        $beforeLast = $tokens[$count - 2] ?? null;

        if (isset($operationTokens[$last])) {
            $isImplicitMember = in_array($last, self::HTTP_MEMBER_OPERATIONS, true);

            if (!in_array($last, self::HTTP_COLLECTION_OPERATIONS, true) && !$isImplicitMember) {
                return null;
            }

            $resourceTokens = array_slice($tokens, 0, -1);
            if (!$this->hasValidResourceTokenCount($resourceTokens)) {
                return null;
            }

            $operation = $last;

            return new CrudTokenizedRouteIntent(
                routeFamily: $routeFamily,
                resourcePath: implode('/', $resourceTokens),
                operation: $operation,
                view: $this->viewFor($operation, null, $defaultView),
                identifierField: $isImplicitMember ? 'slug' : null,
                identifierValue: null,
                tokens: $tokens,
            );
        }

        if (null !== $beforeLast && isset($operationTokens[$beforeLast])) {
            if (!in_array($beforeLast, self::HTTP_MEMBER_OPERATIONS, true)) {
                return null;
            }

            $resourceTokens = array_slice($tokens, 0, -2);
            if (!$this->hasValidResourceTokenCount($resourceTokens)) {
                return null;
            }

            $operation = $beforeLast;

            return new CrudTokenizedRouteIntent(
                routeFamily: $routeFamily,
                resourcePath: implode('/', $resourceTokens),
                operation: $operation,
                view: $this->viewFor($operation, $last, $defaultView),
                identifierField: $this->identifierField($last),
                identifierValue: $last,
                tokens: $tokens,
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
     * @param list<string> $resourceTokens
     */
    private function hasValidResourceTokenCount(array $resourceTokens): bool
    {
        $count = count($resourceTokens);

        if ($count < 1 || $count > self::MAX_RESOURCE_TOKEN_COUNT) {
            return false;
        }

        foreach ($resourceTokens as $resourceToken) {
            if (in_array($resourceToken, [self::CONTEXT_PREFIX_API, $this->backendContextToken()], true)) {
                return false;
            }
        }

        return true;
    }

    private function backendContextToken(): string
    {
        $token = $this->backendContextToken
            ?? ($_ENV['CRUDING_BACKEND_CONTEXT_TOKEN'] ?? null)
            ?? ($_ENV['CRUDING_BACKEND_ROUTE_TOKEN'] ?? null)
            ?? ($_ENV['CRUDING_BACKEND_ROUTE_PREFIX'] ?? null)
            ?? ($_ENV['EASYADMIN_ROUTE_PREFIX'] ?? null)
            ?? self::DEFAULT_BACKEND_CONTEXT_PREFIX;

        return $this->tokenNormalizer->token((string) $token);
    }

    private function isIdentityToken(string $identity): bool
    {
        if (preg_match('/^[1-9][0-9]*$/', $identity)) {
            return true;
        }

        return strlen($identity) >= 18
            && preg_match('/^[a-z0-9][a-z0-9_-]*$/', $identity);
    }

    private function identifierField(string $identity): string
    {
        return preg_match('/^\d+$/', $identity) ? 'id' : 'slug';
    }

    private function viewFor(string $operation, ?string $identity, string $defaultView): string
    {
        if (null !== $identity && !preg_match('/^\d+$/', $identity)) {
            return 'public';
        }

        return self::view_BY_OPERATION[$operation] ?? $defaultView;
    }
}
