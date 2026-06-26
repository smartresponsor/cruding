<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Resource;

use App\Cruding\Dto\Resource\CrudResourceRequest;
use App\Cruding\Dto\Resource\CrudRouteContext;
use App\Cruding\Service\Crud\Resource\CrudResourceProviderLocator;
use App\Cruding\ServiceInterface\Crud\Resource\CrudResourceProviderInterface;
use App\Cruding\Value\Resource\CrudResourceContract;
use PHPUnit\Framework\TestCase;

final class CrudResourceProviderLocatorTest extends TestCase
{
    public function testLocatesProviderByClassNameConvention(): void
    {
        $provider = new AlphaComplianceBriefingview();
        $locator = new CrudResourceProviderLocator([$provider]);
        $context = new CrudRouteContext(
            resource: 'alpha',
            resourcePath: 'alpha',
            operation: 'briefing',
            view: 'briefing',
            viewPath: 'compliance',
            viewToken: 'compliance',
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
        self::assertSame(AlphaComplianceBriefingview::class, $locator->entries()['alpha.compliance.briefing']);
    }
}

final class AlphaComplianceBriefingview implements CrudResourceProviderInterface
{
    public function provide(CrudResourceRequest $request): CrudResourceContract
    {
        return CrudResourceContract::forResource(
            $request->routeContext->view,
            $request->routeContext->toArray(),
            ['body' => []],
        );
    }
}
