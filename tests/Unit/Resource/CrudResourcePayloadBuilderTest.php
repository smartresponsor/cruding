<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Resource;

use App\Cruding\Builder\Resource\CrudResourcePayloadBuilder;
use App\Cruding\DTO\CrudAccessContextDTO;
use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\DTO\CrudOwnershipDTO;
use App\Cruding\DTO\CrudPageDefinitionDTO;
use App\Cruding\DTO\Resource\CrudResourceRequestDTO;
use App\Cruding\DTO\Resource\CrudRouteContextDTO;
use App\Cruding\Factory\Resource\CrudResourceContractFactory;
use App\Cruding\ServiceInterface\Resource\CrudInterfacingProviderResourceBuilderInterface;
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

    public function testResourceContractBuildsTemplateAndFallbackContexts(): void
    {
        $contract = \App\Cruding\ValueObject\Resource\CrudResourceContract::forResource(
            'detail',
            [
                'resourcePath' => 'product/document',
                'resourceLabel' => 'Product document',
                'operation' => 'show',
                'view' => 'detail',
            ],
            ['body' => [['type' => 'document']]],
            [
                'title' => 'Document detail',
                'format' => 'html',
                'custom' => 'meta-value',
            ],
        );

        $template = $contract->toTemplateContext();
        self::assertSame('crud', $template['word']);
        self::assertSame('detail', $template['view']);
        self::assertSame('Document detail', $template['adminProviderPageTitle']);
        self::assertSame('product/document', $template['adminProviderResourceName']);
        self::assertSame('Product document', $template['adminProviderResourceLabel']);
        self::assertSame('show', $template['adminProviderOperation']);
        self::assertSame('detail', $template['adminProviderview']);
        self::assertSame('detail', $template['adminProviderDefaultView']);
        self::assertSame(['detail'], $template['adminProviderViewModes']);
        self::assertSame('html', $template['format']);
        $templateMeta = $template['meta'];
        self::assertIsArray($templateMeta);
        self::assertSame('meta-value', $templateMeta['custom'] ?? null);

        $fallback = $contract->toFallbackData();
        self::assertSame($contract->locations, $fallback['locations']);
        self::assertSame('html', $fallback['format']);
        $fallbackMeta = $fallback['meta'];
        self::assertIsArray($fallbackMeta);
        self::assertSame('meta-value', $fallbackMeta['custom'] ?? null);
    }

    public function testResourceContractFallsBackToSlotLocationsAndDefaults(): void
    {
        $contract = new \App\Cruding\ValueObject\Resource\CrudResourceContract(
            word: 'crud',
            view: 'index',
            slotMap: [],
            workbench: ['routeContext' => [], 'meta' => []],
            slots: [
                'locations' => ['body' => [['type' => 'fallback']]],
                'defaultView' => null,
                'viewModes' => ['table', null, '', 'cards'],
            ],
        );

        $template = $contract->toTemplateContext();
        self::assertSame(['body' => [['type' => 'fallback']]], $template['locations']);
        self::assertSame('Cruding', $template['adminProviderPageTitle']);
        self::assertSame('resource', $template['adminProviderResourceName']);
        self::assertSame('Cruding', $template['adminProviderResourceLabel']);
        self::assertSame('index', $template['adminProviderOperation']);
        self::assertSame('admin', $template['adminProviderview']);
        self::assertSame('table', $template['adminProviderDefaultView']);
        self::assertSame(['table', 'cards'], $template['adminProviderViewModes']);
        self::assertSame('auto', $template['format']);
    }

    public function testResourceContractFactoryMapsRouteOperationAndSanitizesMeta(): void
    {
        $context = new CrudContextDTO('admin', 'edit', 'product', \stdClass::class, 'id', 7, null);
        $access = new CrudAccessContextDTO(
            $context,
            supportsSlug: false,
            supportsId: true,
            ownership: new CrudOwnershipDTO(false, true, false, true, null),
            canView: true,
            canEdit: true,
            canDelete: true,
        );
        $resource = fopen('php://memory', 'r');
        self::assertIsResource($resource);
        $page = new CrudPageDefinitionDTO(
            $context,
            $access,
            'Edit product',
            'product/edit.html.twig',
            meta: [
                'nested' => ['object' => new \stdClass()],
                'resource' => $resource,
                'scalar' => 42,
            ],
        );

        $builder = $this->createMock(CrudInterfacingProviderResourceBuilderInterface::class);
        $builder->expects(self::once())
            ->method('build')
            ->with($page, null, null)
            ->willReturn([
                'workbench' => ['routeContext' => ['operation' => 'show']],
                'locations' => ['body' => [['type' => 'product']]],
            ]);

        $contract = (new CrudResourceContractFactory($builder))->create($page);
        fclose($resource);

        self::assertSame('detail', $contract->view);
        self::assertSame(['body' => [['type' => 'product']]], $contract->locations);
        $pageSlot = $contract->slots['page'] ?? null;
        self::assertIsArray($pageSlot);
        $meta = $pageSlot['meta'] ?? null;
        self::assertIsArray($meta);
        $nested = $meta['nested'] ?? null;
        self::assertIsArray($nested);
        self::assertSame('Edit product', $pageSlot['title'] ?? null);
        self::assertSame('product/edit.html.twig', $contract->slots['sourceView'] ?? null);
        self::assertSame('edit', $contract->slots['sourceOperation'] ?? null);
        self::assertSame(\stdClass::class, $nested['object'] ?? null);
        self::assertSame('stream', $meta['resource'] ?? null);
        self::assertSame(42, $meta['scalar'] ?? null);
    }

    public function testResourceContractFactoryFallsBackToPageOperationForMalformedWorkbench(): void
    {
        $context = new CrudContextDTO('public', 'new', 'product', \stdClass::class, 'id', null, null);
        $access = new CrudAccessContextDTO(
            $context,
            supportsSlug: false,
            supportsId: true,
            ownership: new CrudOwnershipDTO(false, false, false, false, null),
            canView: true,
            canEdit: false,
            canDelete: false,
        );
        $page = new CrudPageDefinitionDTO($context, $access, 'New product', 'product/new.html.twig');

        $builder = $this->createStub(CrudInterfacingProviderResourceBuilderInterface::class);
        $builder->method('build')->willReturn(['workbench' => 'invalid', 'locations' => 'invalid']);

        $contract = (new CrudResourceContractFactory($builder))->create($page);

        self::assertSame('form', $contract->view);
        self::assertSame([], $contract->workbench);
        self::assertSame([], $contract->locations);
    }
}
