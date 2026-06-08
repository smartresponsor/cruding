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
        self::assertSame([
            'vendor/compliance/index.html.twig',
            'vendor/index.html.twig',
            'index.html.twig',
        ], $context->templateCandidates);
        self::assertNotContains('vendor/compliance/briefing.html.twig', $context->templateCandidates);
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

    public function testKeepsSubjectAndItemIdentifierFieldsSeparate(): void
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
        self::assertSame('vendorSlug', $context->subjectField);
        self::assertSame('documentSlug', $context->itemField);
        self::assertSame('slug', $context->subjectIdentifierField());
        self::assertSame('slug', $context->itemIdentifierField());
        self::assertSame('slug', $context->identifierField());
        self::assertSame('w9-form', $context->identifierValue());
    }

    public function testResolvesSurfaceTokenBeforeItemIdentifier(): void
    {
        $resolver = new CrudRouteShapeResolver($this->routerWithRoute(
            'cruding_surface_token_item',
            '/{resource}/{subject}/{surface}/{token}/{item}',
        ));

        $request = Request::create('/vendor/attachment/media/show/acme-inc');
        $request->attributes->set('_route', 'cruding_surface_token_item');
        $request->attributes->set('resource', 'vendor');
        $request->attributes->set('subject', 'attachment');
        $request->attributes->set('surface', 'media');
        $request->attributes->set('token', 'show');
        $request->attributes->set('item', 'acme-inc');

        $context = $resolver->resolve($request);

        self::assertNotNull($context);
        self::assertSame('vendor', $context->resource);
        self::assertSame('media', $context->surfacePath);
        self::assertSame('show', $context->surfaceToken);
        self::assertSame('detail', $context->operation);
        self::assertSame('item', $context->itemField);
        self::assertSame('acme-inc', $context->itemValue);
        self::assertSame('vendor.attachment.media.show.detail', $context->primaryProviderKey());
        self::assertSame([
            'vendor/media/show/index.html.twig',
            'vendor/media/index.html.twig',
            'vendor/index.html.twig',
            'index.html.twig',
        ], $context->templateCandidates);
    }

    public function testRouteMapEntryPreservesFullBusinessChainAndTemplateOverride(): void
    {
        $projectDir = sys_get_temp_dir().'/cruding-route-shape-map-'.bin2hex(random_bytes(4));
        $directory = $projectDir.'/config/platform/routes/ecommerce';
        self::assertTrue(mkdir($directory, 0777, true));
        file_put_contents($directory.'/vendor.yaml', "vendor.attachment.document.show_slug: { path: /vendor/attachment/document/show/{slug}, parser: cruding_surface_token_item, routeKey: vendor.attachment.document.show, object: attachment.document, template: document/show/index.html.twig, resolver: slug, service: App\\Service\\Http\\Vendor\\Attachment\\Document\\VendorAttachmentDocumentShowService }\n");

        $matcher = new \App\Cruding\Service\Surface\CrudRouteMapMatcher(new \App\Cruding\Service\Surface\CrudRouteMapLoader($projectDir));
        $resolver = new CrudRouteShapeResolver(
            $this->routerWithRoute('cruding_surface_token_item', '/{resource}/{subject}/{surface}/{token}/{item}'),
            routeMapMatcher: $matcher,
        );

        $request = Request::create('/vendor/attachment/document/show/w9-form');
        $request->attributes->set('_route', 'cruding_surface_token_item');
        $request->attributes->set('resource', 'vendor');
        $request->attributes->set('subject', 'attachment');
        $request->attributes->set('surface', 'document');
        $request->attributes->set('token', 'show');
        $request->attributes->set('item', 'w9-form');

        $context = $resolver->resolve($request);

        self::assertNotNull($context);
        self::assertSame('vendor.attachment.document.show', $context->primaryProviderKey());
        self::assertSame('document/show/index.html.twig', $context->templateCandidates[0]);
        self::assertIsArray($context->routeMapEntry);
        self::assertSame('attachment.document', $context->routeMapEntry['object']);
        self::assertSame('slug', $context->routeMapEntry['resolver']);
    }

    public function testProviderKeysPreserveGenericSubjectBusinessToken(): void
    {
        $resolver = new CrudRouteShapeResolver($this->routerWithRoute(
            'cruding_surface_action',
            '/{resource}/{subject}/{surface}/{action}',
        ));

        $request = Request::create('/vendor/attachment/document/index');
        $request->attributes->set('_route', 'cruding_surface_action');
        $request->attributes->set('resource', 'vendor');
        $request->attributes->set('subject', 'attachment');
        $request->attributes->set('surface', 'document');
        $request->attributes->set('action', 'index');

        $context = $resolver->resolve($request);

        self::assertNotNull($context);
        self::assertSame([
            'vendor.attachment.document.index',
            'vendor.attachment.document',
            'vendor.document.index',
            'vendor.document',
            'vendor.index',
        ], $context->providerKeys);
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
