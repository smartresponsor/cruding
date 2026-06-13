<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Surface;

use App\Cruding\Dto\Surface\CrudRouteContext;
use App\Cruding\Dto\Surface\CrudSurfaceRequest;
use App\Cruding\Service\Surface\CrudSurfacePayloadBuilder;
use PHPUnit\Framework\TestCase;

final class CrudSurfacePayloadBuilderTest extends TestCase
{
    public function testBuildsLocationBasedSurfaceContractWithoutControllerPayloadKnowledge(): void
    {
        $context = new CrudRouteContext(
            resource: 'alpha',
            resourcePath: 'alpha',
            operation: 'briefing',
            view: 'briefing',
            surfacePath: 'compliance',
            subjectField: 'alphaSlug',
            subjectValue: 'acme-inc',
            itemField: null,
            itemValue: null,
            routeName: 'alpha_compliance_briefing',
            routeTemplate: '/alpha/{alphaSlug}/compliance/briefing',
            routeParameters: ['alphaSlug' => 'sample-subject'],
            providerKeys: ['alpha.compliance.briefing'],
            templateCandidates: ['alpha/compliance/index.html.twig'],
        );
        $request = new CrudSurfaceRequest($context, 'en', 'GET', [], []);

        $contract = CrudSurfacePayloadBuilder::fromRequest($request)
            ->title('Alpha compliance briefing')
            ->block('top', 'alpha_header', ['slug' => 'sample-subject'])
            ->block('body', 'compliance_briefing', ['riskLevel' => 'medium'])
            ->block('right.panel', 'next_action', ['count' => 2])
            ->toContract();

        self::assertSame('briefing', $contract->view);
        self::assertSame('Alpha compliance briefing', $contract->workbench['title']);
        self::assertSame('alpha_header', $contract->locations['top'][0]['key']);
        self::assertSame('compliance_briefing', $contract->locations['body'][0]['type']);
        self::assertSame('next_action', $contract->locations['right.panel'][0]['key']);
        self::assertSame('alpha', $contract->workbench['routeContext']['resource']);
    }
}
