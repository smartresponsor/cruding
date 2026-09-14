<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Resource;

use App\Cruding\Resolver\Resource\CrudRouteProviderKeyResolver;
use App\Cruding\Resolver\Resource\CrudRouteTemplateCandidateResolver;
use App\Cruding\Resolver\Resource\CrudRouteViewResolver;
use App\Cruding\Service\Resource\CrudRouteParameterExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class CrudRouteSupportTest extends TestCase
{
    public function testExtractsOnlyPublicScalarRouteParameters(): void
    {
        $request = Request::create('/alpha');
        $request->attributes->add([
            '_route' => 'cruding_resource_action',
            'resource' => 'alpha',
            'id' => 42,
            'nullable' => null,
            'enabled' => true,
            'disabled' => false,
            'ratio' => 1.5,
            'payload' => ['ignored'],
            'object' => new \stdClass(),
        ]);

        self::assertSame([
            'resource' => 'alpha',
            'id' => 42,
            'nullable' => null,
            'enabled' => 1,
            'disabled' => 0,
            'ratio' => '1.5',
        ], (new CrudRouteParameterExtractor())->routeParameters($request));
    }

    public function testBuildsProviderKeysFromSubjectViewAndDetailAliases(): void
    {
        $keys = (new CrudRouteProviderKeyResolver())->providerKeys(
            resource: 'alpha',
            viewPath: 'document',
            ViewToken: 'show',
            operation: 'detail',
            subjectField: 'subject',
            subjectValue: 'attachment',
        );

        self::assertSame([
            'alpha.attachment.document.show.detail',
            'alpha.attachment.document.show.show',
            'alpha.attachment.document.show.view',
            'alpha.attachment.document.detail',
            'alpha.document.show.detail',
            'alpha.document.show.show',
            'alpha.document.show.view',
            'alpha.document.detail',
            'alpha.detail',
            'alpha.show',
            'alpha.view',
        ], $keys);
    }

    public function testBuildsIndexProviderFallbacksWithoutEmptySubject(): void
    {
        self::assertSame([
            'alpha.document.index',
            'alpha.document',
            'alpha.index',
        ], (new CrudRouteProviderKeyResolver())->providerKeys(
            resource: 'alpha',
            viewPath: 'document',
            ViewToken: null,
            operation: 'index',
            subjectField: 'subject',
            subjectValue: '',
        ));
    }

    public function testResolvesViewAndTemplateCandidatesDeterministically(): void
    {
        $viewResolver = new CrudRouteViewResolver();
        self::assertSame('detail', $viewResolver->viewFromOperation('show'));
        self::assertSame('form', $viewResolver->viewFromOperation('new'));
        self::assertSame('form', $viewResolver->viewFromOperation('edit'));
        self::assertSame('index', $viewResolver->viewFromOperation('index'));

        self::assertSame([
            'alpha/document/show/index.html.twig',
            'alpha/document/index.html.twig',
            'alpha/index.html.twig',
            'index.html.twig',
        ], (new CrudRouteTemplateCandidateResolver())->templateCandidates('alpha', 'document', 'show'));
    }
}
