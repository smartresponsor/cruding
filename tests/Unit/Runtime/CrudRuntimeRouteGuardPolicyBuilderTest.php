<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Runtime;

use App\Cruding\Service\Runtime\CrudRuntimeRouteGuardPolicyBuilder;
use App\Cruding\Service\Runtime\CrudRuntimeTokenNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class CrudRuntimeRouteGuardPolicyBuilderTest extends TestCase
{
    private CrudRuntimeRouteGuardPolicyBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new CrudRuntimeRouteGuardPolicyBuilder(
            normalizer: new CrudRuntimeTokenNormalizer(),
            defaultReservedRootTokens: [
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
            ],
            defaultSurfaceTokens: ['card', 'table', 'gallery', 'compact', 'full', 'detail', 'list'],
            defaultOperationTokens: ['index', 'show', 'new', 'create', 'edit', 'update', 'delete', 'bulk', 'import', 'export', 'archive', 'restore', 'duplicate'],
            defaultResourcePathReservedTokens: ['audit', 'visibility', 'attach', 'detach'],
        );
    }

    public function testScopeTokensAreReservedAndEntityTokensRemainPolicyData(): void
    {
        $policy = $this->builder->build(
            scopeRaw: 'cruding,viewing,interfacing,administering,accessing',
            entityRaw: 'alpha,attachment,media,product,category',
            surfaceTokenRaw: 'card,table,gallery',
            reservedRaw: '',
        );

        self::assertSame(['alpha', 'attachment', 'media', 'product', 'category'], $policy->allowedResourceTokens);
        self::assertContains('viewing', $policy->reservedRootTokens);
        self::assertContains('interfacing', $policy->reservedRootTokens);
        self::assertFalse($policy->hasConflicts());
        self::assertContains('index', $policy->operationTokens);
        self::assertContains('card', $policy->surfaceTokens);

        $matcher = $this->tokenizedMatcher();
        self::assertSame('cruding_surface_token_item', $matcher->match('/alpha/attachment/media/card/123')['_route']);
        self::assertSame('cruding_tokenized_catch_all', $matcher->match('/alpha')['_route']);
        self::assertSame('cruding_tokenized_catch_all', $matcher->match('/alpha/index')['_route']);
        self::assertSame('cruding_tokenized_catch_all', $matcher->match('/alpha/attachment/media/edit/123')['_route']);
    }

    public function testEntityTokenConflictingWithRuntimeScopeIsRejected(): void
    {
        $policy = $this->builder->build(
            scopeRaw: 'cruding,viewing',
            entityRaw: 'alpha,viewing',
            surfaceTokenRaw: 'card',
            reservedRaw: '',
        );

        self::assertTrue($policy->hasConflicts());
        self::assertSame(['viewing'], $policy->conflictingEntityTokens);
        self::assertSame(['alpha'], $policy->allowedResourceTokens);
        self::assertStringContainsString('alpha', $policy->resourceRequirement);
        self::assertStringNotContainsString('viewing', $policy->resourceRequirement);
    }

    public function testSurfaceTokensRemainBeforeTokenizedCatchAllRoutes(): void
    {
        $matcher = $this->tokenizedMatcher();

        self::assertSame('cruding_surface_token_item', $matcher->match('/alpha/attachment/media/card/acme-inc')['_route']);
        self::assertSame('cruding_tokenized_catch_all', $matcher->match('/alpha/card')['_route']);
    }

    private function tokenizedMatcher(): UrlMatcher
    {
        $collection = new RouteCollection();
        $collection->add('cruding_surface_token_item', new Route(
            '/{resource}/{subject}/{surface}/{token}/{item}',
            [],
            [
                'resource' => '[a-z][a-z0-9_-]*',
                'subject' => '[A-Za-z0-9][A-Za-z0-9_-]*',
                'surface' => '[a-z0-9][a-z0-9_-]*',
                'token' => 'card|table|gallery',
                'item' => '[A-Za-z0-9][A-Za-z0-9_-]*',
            ],
        ));
        $collection->add('cruding_tokenized_catch_all', new Route(
            '/{crudPath}',
            [],
            ['crudPath' => '.+'],
        ));

        return new UrlMatcher($collection, new RequestContext());
    }
}
