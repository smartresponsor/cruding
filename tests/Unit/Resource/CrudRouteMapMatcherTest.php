<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Resource;

use App\Cruding\Service\Resource\CrudRouteMapLoader;
use App\Cruding\Service\Resource\CrudRouteMapMatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class CrudRouteMapMatcherTest extends TestCase
{
    public function testLoadsAndMatchesOneLineRouteMapEntry(): void
    {
        $projectDir = $this->createProjectDir([
            'alpha.attachment.document.show_slug: { path: /alpha/attachment/document/show/{slug}, parser: cruding_view_token_item, routeKey: alpha.attachment.document.show, object: attachment.document, template: document/show/index.html.twig, resolver: slug, service: App\\Service\\Http\\Alpha\\Attachment\\Document\\AlphaAttachmentDocumentShowService }',
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

    public function testScansSiblingComponentRouteMaps(): void
    {
        $baseDir = sys_get_temp_dir().'/cruding-route-map-host-'.bin2hex(random_bytes(4));
        $projectDir = $baseDir.'/Host';
        self::assertTrue(mkdir($projectDir.'/config/platform/routes', 0777, true));

        $vendoringDirectory = $baseDir.'/Vendoring/config/platform/routes/crud';
        self::assertTrue(is_dir($vendoringDirectory) || mkdir($vendoringDirectory, 0777, true));
        file_put_contents($vendoringDirectory.'/vendor.yaml', "vendor.index: { path: /vendor/index, service: App\\Vendoring\\Service\\Http\\Vendor\\VendorIndexService }\n");

        $matcher = new CrudRouteMapMatcher(new CrudRouteMapLoader($projectDir));
        $entry = $matcher->match(Request::create('/vendor/index'));

        self::assertNotNull($entry);
        self::assertSame('vendor.index', $entry->nameEntity);
        self::assertSame('App\\Vendoring\\Service\\Http\\Vendor\\VendorIndexService', $entry->service);
    }

    public function testLoaderIgnoresMalformedEntriesAndPreservesNestedInlineValues(): void
    {
        $projectDir = $this->createProjectDir([
            '',
            '# comment',
            'missing-colon',
            'missing_brace: path: /ignored',
            'bad name: { path: /ignored }',
            'missing.path: { parser: ignored }',
            'nested.route: { path: /nested/{id}, service: App\\Nested\\Service, metadata: [one,two], options: (alpha,beta) }',
        ]);
        file_put_contents($projectDir.'/config/platform/routes/ecommerce/ignored.txt', 'ignored: { path: /ignored }'.PHP_EOL);

        $entries = (new CrudRouteMapLoader($projectDir))->entries();

        self::assertCount(1, $entries);
        self::assertSame('nested.route', $entries[0]->nameEntity);
        self::assertSame('/nested/{id}', $entries[0]->path);
        self::assertSame('App\\Nested\\Service', $entries[0]->service);
        self::assertSame('[one,two]', $entries[0]->extra['metadata'] ?? null);
        self::assertSame('(alpha,beta)', $entries[0]->extra['options'] ?? null);
    }

    public function testLoaderReturnsEmptyListWhenRouteDirectoryIsMissing(): void
    {
        $baseDir = sys_get_temp_dir().'/cruding-route-map-empty-'.bin2hex(random_bytes(4));
        $projectDir = $baseDir.'/Host';
        self::assertTrue(mkdir($projectDir, 0777, true));

        self::assertSame([], (new CrudRouteMapLoader($projectDir))->entries());
    }

    /** @param list<string> $lines */
    private function createProjectDir(array $lines): string
    {
        $baseDir = sys_get_temp_dir().'/cruding-route-map-'.bin2hex(random_bytes(4));
        $projectDir = $baseDir.'/Host';
        $directory = $projectDir.'/config/platform/routes/ecommerce';
        self::assertTrue(mkdir($directory, 0777, true));
        file_put_contents($directory.'/alpha.yaml', implode("\n", $lines)."\n");

        return $projectDir;
    }
}
