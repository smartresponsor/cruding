<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Resource;

use App\Cruding\Builder\Resource\CrudResourcePayloadBuilder;
use App\Cruding\DTO\Resource\CrudResourceRequestDTO;
use App\Cruding\DTO\Resource\CrudRouteContextDTO;
use PHPUnit\Framework\TestCase;

final class CrudResourcePayloadBuilderTest extends TestCase
{
    public function testBuildsLocationBasedViewContractWithoutControllerPayloadKnowledge(): void
    {
        $context = new CrudRouteContextDTO(
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
        $request = new CrudResourceRequestDTO($context, 'en', 'GET', [], []);

        $contract = CrudResourcePayloadBuilder::fromRequest($request)
            ->title('Alpha compliance briefing')
            ->block('top', 'alpha_header', ['slug' => 'sample-subject'])
            ->block('body', 'compliance_briefing', ['riskLevel' => 'medium'])
            ->block('right.panel', 'next_action', ['count' => 2])
            ->toContract();

        self::assertSame('briefing', $contract->view);
        self::assertSame('Alpha compliance briefing', $contract->workbench['title']);

        $top = $contract->locations['top'] ?? null;
        $body = $contract->locations['body'] ?? null;
        $right = $contract->locations['right.panel'] ?? null;
        $routeContext = $contract->workbench['routeContext'] ?? null;
        self::assertIsArray($top);
        self::assertIsArray($body);
        self::assertIsArray($right);
        self::assertIsArray($routeContext);
        self::assertIsArray($top[0] ?? null);
        self::assertIsArray($body[0] ?? null);
        self::assertIsArray($right[0] ?? null);

        self::assertSame('alpha-header', $top[0]['key']);
        self::assertSame('compliance-briefing', $body[0]['type']);
        self::assertSame('next-action', $right[0]['key']);
        self::assertSame('alpha', $routeContext['resource']);
    }
}
