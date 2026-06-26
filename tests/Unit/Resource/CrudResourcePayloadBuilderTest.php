<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Resource;

use App\Cruding\Dto\Resource\CrudResourceRequest;
use App\Cruding\Dto\Resource\CrudRouteContext;
use App\Cruding\Service\Crud\Resource\CrudResourcePayloadBuilder;
use PHPUnit\Framework\TestCase;

final class CrudResourcePayloadBuilderTest extends TestCase
{
    public function testBuildsLocationBasedViewContractWithoutControllerPayloadKnowledge(): void
    {
        $context = new CrudRouteContext(
            resource: 'alpha',
            resourcePath: 'alpha',
            operation: 'briefing',
            view: 'briefing',
            viewPath: 'compliance',
            viewToken: 'compliance',
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
        $request = new CrudResourceRequest($context, 'en', 'GET', [], []);

        $contract = CrudResourcePayloadBuilder::fromRequest($request)
            ->title('Alpha compliance briefing')
            ->block('top', 'alpha_header', ['slug' => 'sample-subject'])
            ->block('body', 'compliance_briefing', ['riskLevel' => 'medium'])
            ->block('right.panel', 'next_action', ['count' => 2])
            ->toContract();

        self::assertSame('briefing', $contract->view);
        self::assertSame('Alpha compliance briefing', $contract->workbench['title']);
        self::assertSame('alpha-header', $contract->locations['top'][0]['key']);
        self::assertSame('compliance-briefing', $contract->locations['body'][0]['type']);
        self::assertSame('next-action', $contract->locations['right.panel'][0]['key']);
        self::assertSame('alpha', $contract->workbench['routeContext']['resource']);
    }
}
