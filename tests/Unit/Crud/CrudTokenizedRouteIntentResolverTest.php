<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud;

use App\Cruding\Service\Crud\CrudReservedRouteTokenPolicy;
use App\Cruding\Service\Crud\CrudRouteTokenNormalizer;
use App\Cruding\Service\Crud\CrudTokenizedRouteIntentResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class CrudTokenizedRouteIntentResolverTest extends TestCase
{
    public function testMultiTokenPathWithoutExplicitOperationTokenIsNotCrudIntent(): void
    {
        $intent = $this->resolver()->resolveWeb($this->request('/access/password'));

        self::assertNull($intent);
    }

    public function testSingleTokenPathStillResolvesIndexIntent(): void
    {
        $intent = $this->resolver()->resolveWeb($this->request('/product'));

        self::assertNotNull($intent);
        self::assertSame('product', $intent->resourcePath);
        self::assertSame('index', $intent->operation);
        self::assertNull($intent->identifierField);
        self::assertNull($intent->identifierValue);
    }

    public function testExplicitOperationTokenWithIdentityResolvesShowIntent(): void
    {
        $intent = $this->resolver()->resolveWeb($this->request('/product/show/gold'));

        self::assertNotNull($intent);
        self::assertSame('product', $intent->resourcePath);
        self::assertSame('show', $intent->operation);
        self::assertSame('slug', $intent->identifierField);
        self::assertSame('gold', $intent->identifierValue);
    }

    private function request(string $path): Request
    {
        $request = Request::create($path);
        $request->attributes->set('crudPath', ltrim($path, '/'));

        return $request;
    }

    private function resolver(): CrudTokenizedRouteIntentResolver
    {
        return new CrudTokenizedRouteIntentResolver(
            new CrudRouteTokenNormalizer(),
            new CrudReservedRouteTokenPolicy(
                surfaceTokens: [],
                operationTokens: ['index', 'show', 'new', 'create', 'edit', 'update', 'delete', 'import', 'bulk', 'archive', 'restore', 'duplicate', 'verify'],
            ),
        );
    }
}
