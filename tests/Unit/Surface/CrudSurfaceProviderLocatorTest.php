<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Surface;

use App\Cruding\Dto\Surface\CrudRouteContext;
use App\Cruding\Dto\Surface\CrudSurfaceRequest;
use App\Cruding\Service\Surface\CrudSurfaceProviderLocator;
use App\Cruding\ServiceInterface\Surface\CrudSurfaceProviderInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use PHPUnit\Framework\TestCase;

final class CrudSurfaceProviderLocatorTest extends TestCase
{
    public function testLocatesProviderByClassNameConvention(): void
    {
        $provider = new VendorComplianceBriefingSurface();
        $locator = new CrudSurfaceProviderLocator([$provider]);

        $context = new CrudRouteContext(
            resource: 'vendor',
            resourcePath: 'vendor',
            operation: 'briefing',
            view: 'briefing',
            surfacePath: 'compliance',
            subjectField: 'vendorSlug',
            subjectValue: 'acme-inc',
            itemField: null,
            itemValue: null,
            routeName: 'vendor_compliance_briefing',
            routeTemplate: '/vendor/{vendorSlug}/compliance/briefing',
            routeParameters: ['vendorSlug' => 'acme-inc'],
            providerKeys: ['vendor.compliance.briefing'],
            templateCandidates: ['vendor/compliance/index.html.twig'],
        );

        self::assertSame($provider, $locator->locate($context));
        self::assertContains('vendor.compliance.briefing', $locator->keys());
        self::assertSame(VendorComplianceBriefingSurface::class, $locator->entries()['vendor.compliance.briefing']);
    }
}

final class VendorComplianceBriefingSurface implements CrudSurfaceProviderInterface
{
    public function provide(CrudSurfaceRequest $request): CrudSurfaceContract
    {
        return CrudSurfaceContract::forSurface(
            $request->routeContext->view,
            $request->routeContext->toArray(),
            ['body' => []],
        );
    }
}
