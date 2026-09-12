<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Resource;

use App\Cruding\DTO\Resource\CrudResourceRequestDTO;
use App\Cruding\DTO\Resource\CrudRouteContextDTO;
use App\Cruding\Service\Resource\CrudResourceProviderLocator;
use App\Cruding\ServiceInterface\Resource\CrudResourceProviderInterface;
use App\Cruding\ValueObject\Resource\CrudResourceContract;
use PHPUnit\Framework\TestCase;

final class CrudResourceProviderLocatorTest extends TestCase
{
    public function testLocatesProviderByClassNameConvention(): void
    {
        $provider = new AlphaComplianceBriefingview();
        $locator = new CrudResourceProviderLocator([$provider]);
        $context = new CrudRouteContextDTO(
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
    public function provide(CrudResourceRequestDTO $request): CrudResourceContract
    {
        return CrudResourceContract::forResource(
            $request->routeContext->view,
            $request->routeContext->toArray(),
            ['body' => []],
        );
    }
}
