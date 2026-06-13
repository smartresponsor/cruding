<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Surface;

use App\Cruding\Service\Surface\CrudRouteMapLoader;
use App\Cruding\Service\Surface\CrudRouteMapMatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class CrudRouteMapMatcherTest extends TestCase
{
    public function testLoadsAndMatchesOneLineRouteMapEntry(): void
    {
        $projectDir = $this->createProjectDir([
            'alpha.attachment.document.show_slug: { path: /alpha/attachment/document/show/{slug}, parser: cruding_surface_token_item, routeKey: alpha.attachment.document.show, object: attachment.document, template: document/show/index.html.twig, resolver: slug, service: App\\Service\\Http\\Alpha\\Attachment\\Document\\AlphaAttachmentDocumentShowService }',
        ]);

        $matcher = new CrudRouteMapMatcher(new CrudRouteMapLoader($projectDir));
        $entry = $matcher->match(Request::create('/alpha/attachment/document/show/w9-form'));

        self::assertNotNull($entry);
        self::assertSame('alpha.attachment.document.show_slug', $entry->nameEntity);
        self::assertSame('alpha.attachment.document.show', $entry->canonicalKey());
        self::assertSame('attachment.document', $entry->object);
        self::assertSame('document/show/index.html.twig', $entry->template);
        self::assertSame('slug', $entry->identifierResolver());
    }

    public function testPrefersSlugEntryForNonNumericSlugPath(): void
    {
        $projectDir = $this->createProjectDir([
            'catalog.product.show_id: { path: /catalog/product/show/{id}, resolver: id, service: App\\Service\\Http\\Catalog\\Product\\CatalogProductShowService }',
            'catalog.product.show_slug: { path: /catalog/product/show/{slug}, resolver: slug, service: App\\Service\\Http\\Catalog\\Product\\CatalogProductShowService }',
        ]);

        $matcher = new CrudRouteMapMatcher(new CrudRouteMapLoader($projectDir));
        $entry = $matcher->match(Request::create('/catalog/product/show/iphone-15'));

        self::assertNotNull($entry);
        self::assertSame('catalog.product.show_slug', $entry->nameEntity);
        self::assertSame('slug', $entry->identifierResolver());
    }

    /**
     * @param list<string> $lines
     */
    private function createProjectDir(array $lines): string
    {
        $projectDir = sys_get_temp_dir().'/cruding-route-map-'.bin2hex(random_bytes(4));
        $directory = $projectDir.'/config/platform/routes/ecommerce';
        self::assertTrue(mkdir($directory, 0777, true));
        file_put_contents($directory.'/alpha.yaml', implode("\n", $lines)."\n");

        return $projectDir;
    }
}
