<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Surface;

use App\Cruding\Dto\Surface\CrudRouteContext;
use App\Cruding\Dto\Surface\CrudSurfaceRequest;
use App\Cruding\Service\Crud\Surface\CrudSurfaceProviderLocator;
use App\Cruding\ServiceInterface\Surface\CrudSurfaceProviderInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use PHPUnit\Framework\TestCase;

final class CrudSurfaceProviderLocatorTest extends TestCase
{
    public function testLocatesProviderByClassNameConvention(): void
    {
        $provider = new AlphaComplianceBriefingSurface();
        $locator = new CrudSurfaceProviderLocator([$provider]);
        $context = new CrudRouteContext(
            resource: 'alpha',
            resourcePath: 'alpha',
            operation: 'briefing',
            view: 'briefing',
            surfacePath: 'compliance',
            subjectField: 'alphaSlug',
            subjectValue: 'sample-subject',
            itemField: null,
            itemValue: null,
            routeName: 'alpha_compliance_briefing',
            routeTemplate: '/alpha/{alphaSlug}/compliance/briefing',
            routeParameters: ['alphaSlug' => 'sample-subject'],
            providerKeys: ['alpha.compliance.briefing'],
            templateCandidates: ['alpha/compliance/index.html.twig'],
        );

        self::assertSame($provider, $locator->locate($context));
        self::assertContains('alpha.compliance.briefing', $locator->keys());
        self::assertSame(AlphaComplianceBriefingSurface::class, $locator->entries()['alpha.compliance.briefing']);
    }
}

final class AlphaComplianceBriefingSurface implements CrudSurfaceProviderInterface
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
