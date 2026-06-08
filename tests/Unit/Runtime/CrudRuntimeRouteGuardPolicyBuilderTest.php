<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Runtime;

use App\Cruding\Service\Runtime\CrudRuntimeRouteGuardPolicyBuilder;
use App\Cruding\Service\Runtime\CrudRuntimeTokenNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
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

    public function testScopeTokensAreReservedAndEntityTokensBecomeRouteRequirements(): void
    {
        $policy = $this->builder->build(
            scopeRaw: 'cruding,viewing,interfacing,administering,accessing',
            entityRaw: 'vendor,attachment,media,product,category',
            surfaceTokenRaw: 'card,table,gallery',
            reservedRaw: '',
        );

        self::assertSame(['vendor', 'attachment', 'media', 'product', 'category'], $policy->allowedResourceTokens);
        self::assertContains('viewing', $policy->reservedRootTokens);
        self::assertContains('interfacing', $policy->reservedRootTokens);
        self::assertFalse($policy->hasConflicts());

        $matcher = $this->matcher($policy->resourceRequirement, $policy->resourcePathRequirement, $policy->surfaceTokenRequirement, $policy->identitySlugRequirement);

        self::assertSame('cruding_surface_token_item', $matcher->match('/vendor/attachment/media/card/123')['_route']);
        self::assertSame('cruding_index_root', $matcher->match('/vendor')['_route']);
        self::assertSame('cruding_show_slug', $matcher->match('/vendor/acme-inc')['_route']);
        self::assertSame('cruding_index_token_no_slash', $matcher->match('/vendor/index')['_route']);
        $this->assertRouteDoesNotMatch($matcher, '/vendor/show');
        $this->assertRouteDoesNotMatch($matcher, '/vendor/new');
        $this->assertRouteDoesNotMatch($matcher, '/vendor/import');
        $this->assertRouteDoesNotMatch($matcher, '/admin');
        $this->assertRouteDoesNotMatch($matcher, '/login');
        $this->assertRouteDoesNotMatch($matcher, '/viewing');
        $this->assertRouteDoesNotMatch($matcher, '/interfacing');
    }

    public function testEntityTokenConflictingWithRuntimeScopeIsRejected(): void
    {
        $policy = $this->builder->build(
            scopeRaw: 'cruding,viewing',
            entityRaw: 'vendor,viewing',
            surfaceTokenRaw: 'card',
            reservedRaw: '',
        );

        self::assertTrue($policy->hasConflicts());
        self::assertSame(['viewing'], $policy->conflictingEntityTokens);
        self::assertSame(['vendor'], $policy->allowedResourceTokens);
        self::assertStringContainsString('vendor', $policy->resourceRequirement);
        self::assertStringNotContainsString('viewing', $policy->resourceRequirement);
    }

    public function testSurfaceTokensAreNotRuntimeEntities(): void
    {
        $policy = $this->builder->build(
            scopeRaw: 'cruding,viewing',
            entityRaw: 'vendor',
            surfaceTokenRaw: 'card',
            reservedRaw: '',
        );

        $matcher = $this->matcher($policy->resourceRequirement, $policy->resourcePathRequirement, $policy->surfaceTokenRequirement, $policy->identitySlugRequirement);

        self::assertSame('cruding_surface_token_item', $matcher->match('/vendor/attachment/media/card/acme-inc')['_route']);
        $this->assertRouteDoesNotMatch($matcher, '/show');
        $this->assertRouteDoesNotMatch($matcher, '/vendor/card');
    }

    private function matcher(string $resourceRequirement, string $resourcePathRequirement, string $surfaceTokenRequirement, string $identitySlugRequirement): UrlMatcher
    {
        $collection = new RouteCollection();
        $collection->add('cruding_surface_token_item', new Route(
            '/{resource}/{subject}/{surface}/{token}/{item}',
            [],
            [
                'resource' => $resourceRequirement,
                'subject' => '(?!new$|edit$|delete$)[A-Za-z0-9][A-Za-z0-9_-]*',
                'surface' => '[a-z0-9][a-z0-9_-]*',
                'token' => $surfaceTokenRequirement,
                'item' => '[A-Za-z0-9][A-Za-z0-9_-]*',
            ],
        ));
        $collection->add('cruding_index_token_no_slash', new Route(
            '/{resourcePath}/index',
            [],
            [
                'resourcePath' => $resourcePathRequirement,
            ],
        ));
        $collection->add('cruding_index_token', new Route(
            '/{resourcePath}/index/',
            [],
            [
                'resourcePath' => $resourcePathRequirement,
            ],
        ));
        $collection->add('cruding_show_slug', new Route(
            '/{resourcePath}/{slug}',
            [],
            [
                'resourcePath' => $resourcePathRequirement,
                'slug' => $identitySlugRequirement,
            ],
        ));
        $collection->add('cruding_index_root', new Route(
            '/{resourcePath}',
            [],
            [
                'resourcePath' => $resourcePathRequirement,
            ],
        ));

        return new UrlMatcher($collection, new RequestContext());
    }

    private function assertRouteDoesNotMatch(UrlMatcher $matcher, string $path): void
    {
        try {
            $matcher->match($path);
        } catch (ResourceNotFoundException) {
            self::assertTrue(true);

            return;
        }

        self::fail(sprintf('Expected route path "%s" not to match Cruding routes.', $path));
    }
}
