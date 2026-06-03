<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Surface;

use App\Cruding\Service\Surface\CrudRouteShapeResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final class CrudRouteShapeResolverTest extends TestCase
{
    public function testResolvesLiteralResourceSurfaceActionRoute(): void
    {
        $resolver = new CrudRouteShapeResolver($this->routerWithRoute(
            'vendor_compliance_briefing',
            '/vendor/{vendorSlug}/compliance/briefing',
        ));

        $request = Request::create('/vendor/acme-inc/compliance/briefing');
        $request->attributes->set('_route', 'vendor_compliance_briefing');
        $request->attributes->set('vendorSlug', 'acme-inc');

        $context = $resolver->resolve($request);

        self::assertNotNull($context);
        self::assertSame('vendor', $context->resource);
        self::assertSame('compliance', $context->surfacePath);
        self::assertSame('briefing', $context->operation);
        self::assertSame('vendorSlug', $context->subjectField);
        self::assertSame('acme-inc', $context->subjectValue);
        self::assertSame('vendor.compliance.briefing', $context->primaryProviderKey());
        self::assertContains('vendor/compliance/briefing.html.twig', $context->templateCandidates);
    }

    public function testResolvesGenericDynamicSurfaceActionRoute(): void
    {
        $resolver = new CrudRouteShapeResolver($this->routerWithRoute(
            'cruding_surface_action',
            '/{resource}/{subject}/{surface}/{action}',
        ));

        $request = Request::create('/vendor/acme-inc/compliance/briefing');
        $request->attributes->set('_route', 'cruding_surface_action');
        $request->attributes->set('resource', 'vendor');
        $request->attributes->set('subject', 'acme-inc');
        $request->attributes->set('surface', 'compliance');
        $request->attributes->set('action', 'briefing');

        $context = $resolver->resolve($request);

        self::assertNotNull($context);
        self::assertSame('vendor', $context->resource);
        self::assertSame('compliance', $context->surfacePath);
        self::assertSame('briefing', $context->operation);
        self::assertSame('subject', $context->subjectField);
        self::assertSame('acme-inc', $context->subjectValue);
    }

    public function testResolvesItemActionRoute(): void
    {
        $resolver = new CrudRouteShapeResolver($this->routerWithRoute(
            'vendor_document_preview',
            '/vendor/{vendorSlug}/document/{documentSlug}/preview',
        ));

        $request = Request::create('/vendor/acme-inc/document/w9-form/preview');
        $request->attributes->set('_route', 'vendor_document_preview');
        $request->attributes->set('vendorSlug', 'acme-inc');
        $request->attributes->set('documentSlug', 'w9-form');

        $context = $resolver->resolve($request);

        self::assertNotNull($context);
        self::assertSame('vendor', $context->resource);
        self::assertSame('document', $context->surfacePath);
        self::assertSame('preview', $context->operation);
        self::assertSame('documentSlug', $context->itemField);
        self::assertSame('w9-form', $context->itemValue);
        self::assertSame('vendor.document.preview', $context->primaryProviderKey());
    }

    private function routerWithRoute(string $name, string $path): RouterInterface
    {
        $collection = new RouteCollection();
        $collection->add($name, new Route($path));

        $router = $this->createMock(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($collection);

        return $router;
    }
}
