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
        $request = new CrudSurfaceRequest($context, 'en', 'GET', [], []);

        $contract = CrudSurfacePayloadBuilder::fromRequest($request)
            ->title('Vendor compliance briefing')
            ->block('top', 'vendor_header', ['slug' => 'acme-inc'])
            ->block('body', 'compliance_briefing', ['riskLevel' => 'medium'])
            ->block('right.panel', 'next_action', ['count' => 2])
            ->toContract();

        self::assertSame('briefing', $contract->view);
        self::assertSame('Vendor compliance briefing', $contract->workbench['title']);
        self::assertSame('vendor_header', $contract->locations['top'][0]['key']);
        self::assertSame('compliance_briefing', $contract->locations['body'][0]['type']);
        self::assertSame('next_action', $contract->locations['right.panel'][0]['key']);
        self::assertSame('vendor', $contract->workbench['routeContext']['resource']);
    }
}
