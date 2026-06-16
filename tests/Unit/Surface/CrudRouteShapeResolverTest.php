<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Surface;

use App\Cruding\Service\Crud\Surface\CrudRouteMapLoader;
use App\Cruding\Service\Crud\Surface\CrudRouteMapMatcher;
use App\Cruding\Service\Crud\Surface\CrudRouteShapeResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final class CrudRouteShapeResolverTest extends TestCase
{
    public function testResolvesLiteralResourceSurfaceActionRoute(): void
    {
        $resolver = new CrudRouteShapeResolver($this->routerWithRoute('alpha_compliance_briefing', '/alpha/{alphaSlug}/compliance/briefing'));
        $request = Request::create('/alpha/acme-inc/compliance/briefing');
        $request->attributes->set('_route', 'alpha_compliance_briefing');
        $request->attributes->set('alphaSlug', 'acme-inc');
        $context = $resolver->resolve($request);

        self::assertNotNull($context);
        self::assertSame('alpha', $context->resource);
        self::assertSame('compliance', $context->surfacePath);
        self::assertSame('briefing', $context->operation);
        self::assertSame('alphaSlug', $context->subjectField);
        self::assertSame('acme-inc', $context->subjectValue);
        self::assertSame('alpha.compliance.briefing', $context->primaryProviderKey());
        self::assertSame(['alpha/compliance/index.html.twig', 'alpha/index.html.twig', 'index.html.twig'], $context->templateCandidates);
        self::assertNotContains('alpha/compliance/briefing.html.twig', $context->templateCandidates);
    }

    public function testResolvesGenericDynamicSurfaceActionRoute(): void
    {
        $resolver = new CrudRouteShapeResolver($this->routerWithRoute('cruding_surface_action', '/{resource}/{subject}/{surface}/{action}'));
        $request = Request::create('/alpha/acme-inc/compliance/briefing');
        $request->attributes->set('_route', 'cruding_surface_action');
        $request->attributes->set('resource', 'alpha');
        $request->attributes->set('subject', 'acme-inc');
        $request->attributes->set('surface', 'compliance');
        $request->attributes->set('action', 'briefing');
        $context = $resolver->resolve($request);

        self::assertNotNull($context);
        self::assertSame('alpha', $context->resource);
        self::assertSame('compliance', $context->surfacePath);
        self::assertSame('briefing', $context->operation);
        self::assertSame('subject', $context->subjectField);
        self::assertSame('acme-inc', $context->subjectValue);
    }

    public function testResolvesItemActionRoute(): void
    {
        $resolver = new CrudRouteShapeResolver($this->routerWithRoute('alpha_document_preview', '/alpha/{alphaSlug}/document/{documentSlug}/preview'));
        $request = Request::create('/alpha/acme-inc/document/w9-form/preview');
        $request->attributes->set('_route', 'alpha_document_preview');
        $request->attributes->set('alphaSlug', 'acme-inc');
        $request->attributes->set('documentSlug', 'w9-form');
        $context = $resolver->resolve($request);

        self::assertNotNull($context);
        self::assertSame('alpha', $context->resource);
        self::assertSame('document', $context->surfacePath);
        self::assertSame('preview', $context->operation);
        self::assertSame('documentSlug', $context->itemField);
        self::assertSame('w9-form', $context->itemValue);
        self::assertSame('alpha.document.preview', $context->primaryProviderKey());
    }

    public function testKeepsSubjectAndItemIdentifierFieldsSeparate(): void
    {
        $resolver = new CrudRouteShapeResolver($this->routerWithRoute('alpha_document_preview', '/alpha/{alphaSlug}/document/{documentSlug}/preview'));
        $request = Request::create('/alpha/acme-inc/document/w9-form/preview');
        $request->attributes->set('_route', 'alpha_document_preview');
        $request->attributes->set('alphaSlug', 'acme-inc');
        $request->attributes->set('documentSlug', 'w9-form');
        $context = $resolver->resolve($request);

        self::assertNotNull($context);
        self::assertSame('alphaSlug', $context->subjectField);
        self::assertSame('documentSlug', $context->itemField);
        self::assertSame('slug', $context->subjectIdentifierField());
        self::assertSame('slug', $context->itemIdentifierField());
        self::assertSame('slug', $context->identifierField());
        self::assertSame('w9-form', $context->identifierValue());
    }

    public function testResolvesSurfaceTokenBeforeItemIdentifier(): void
    {
        $resolver = new CrudRouteShapeResolver($this->routerWithRoute('cruding_surface_token_item', '/{resource}/{subject}/{surface}/{token}/{item}'));
        $request = Request::create('/alpha/attachment/media/show/acme-inc');
        $request->attributes->set('_route', 'cruding_surface_token_item');
        $request->attributes->set('resource', 'alpha');
        $request->attributes->set('subject', 'attachment');
        $request->attributes->set('surface', 'media');
        $request->attributes->set('token', 'show');
        $request->attributes->set('item', 'acme-inc');
        $context = $resolver->resolve($request);

        self::assertNotNull($context);
        self::assertSame('alpha', $context->resource);
        self::assertSame('media', $context->surfacePath);
        self::assertSame('show', $context->surfaceToken);
        self::assertSame('detail', $context->operation);
        self::assertSame('item', $context->itemField);
        self::assertSame('acme-inc', $context->itemValue);
        self::assertSame('alpha.attachment.media.show.detail', $context->primaryProviderKey());
        self::assertSame([
            'alpha/media/show/index.html.twig',
            'alpha/media/index.html.twig',
            'alpha/index.html.twig',
            'index.html.twig',
        ], $context->templateCandidates);
    }

    public function testRouteMapEntryPreservesFullBusinessChainAndTemplateOverride(): void
    {
        $projectDir = sys_get_temp_dir().'/cruding-route-shape-map-'.bin2hex(random_bytes(4));
        $directory = $projectDir.'/config/platform/routes/ecommerce';
        self::assertTrue(mkdir($directory, 0777, true));
        file_put_contents($directory.'/alpha.yaml', "alpha.attachment.document.show_slug: { path: /alpha/attachment/document/show/{slug}, parser: cruding_surface_token_item, routeKey: alpha.attachment.document.show, object: attachment.document, template: document/show/index.html.twig, resolver: slug, service: App\\Service\\Http\\Alpha\\Attachment\\Document\\AlphaAttachmentDocumentShowService }\n");

        $matcher = new CrudRouteMapMatcher(new CrudRouteMapLoader($projectDir));
        $resolver = new CrudRouteShapeResolver(
            $this->routerWithRoute('cruding_surface_token_item', '/{resource}/{subject}/{surface}/{token}/{item}'),
            routeMapMatcher: $matcher,
        );

        $request = Request::create('/alpha/attachment/document/show/w9-form');
        $request->attributes->set('_route', 'cruding_surface_token_item');
        $request->attributes->set('resource', 'alpha');
        $request->attributes->set('subject', 'attachment');
        $request->attributes->set('surface', 'document');
        $request->attributes->set('token', 'show');
        $request->attributes->set('item', 'w9-form');
        $context = $resolver->resolve($request);

        self::assertNotNull($context);
        self::assertSame('alpha.attachment.document.show', $context->primaryProviderKey());
        self::assertSame('document/show/index.html.twig', $context->templateCandidates[0]);
        self::assertIsArray($context->routeMapEntry);
        self::assertSame('attachment.document', $context->routeMapEntry['object']);
        self::assertSame('slug', $context->routeMapEntry['resolver']);
    }

    public function testProviderKeysPreserveGenericSubjectBusinessToken(): void
    {
        $resolver = new CrudRouteShapeResolver($this->routerWithRoute('cruding_surface_action', '/{resource}/{subject}/{surface}/{action}'));
        $request = Request::create('/alpha/attachment/document/index');
        $request->attributes->set('_route', 'cruding_surface_action');
        $request->attributes->set('resource', 'alpha');
        $request->attributes->set('subject', 'attachment');
        $request->attributes->set('surface', 'document');
        $request->attributes->set('action', 'index');
        $context = $resolver->resolve($request);

        self::assertNotNull($context);
        self::assertSame([
            'alpha.attachment.document.index',
            'alpha.attachment.document',
            'alpha.document.index',
            'alpha.document',
            'alpha.index',
        ], $context->providerKeys);
    }

    private function routerWithRoute(string $nameEntity, string $path): RouterInterface
    {
        $collection = new RouteCollection();
        $collection->add($nameEntity, new Route($path));

        $router = $this->createMock(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($collection);

        return $router;
    }
}
