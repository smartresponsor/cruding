<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\DependencyInjection\Compiler;

use App\Cruding\DependencyInjection\Compiler\CrudSurfaceServiceLocatorPass;
use App\Cruding\Service\Surface\CrudSurfaceServiceLocator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class CrudSurfaceServiceLocatorPassTest extends TestCase
{
    public function testCollectsHostAndComponentHttpSurfaceServices(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(CrudSurfaceServiceLocator::class, new Definition(CrudSurfaceServiceLocator::class));
        $container->setDefinition('App\\Service\\Http\\Host\\HostIndexService', new Definition('App\\Service\\Http\\Host\\HostIndexService'));
        $container->setDefinition('App\\Vendoring\\Service\\Http\\Vendor\\Attachment\\Document\\VendorAttachmentDocumentIndexService', new Definition('App\\Vendoring\\Service\\Http\\Vendor\\Attachment\\Document\\VendorAttachmentDocumentIndexService'));
        $container->setDefinition('App\\Something\\ElseService', new Definition('App\\Something\\ElseService'));

        (new CrudSurfaceServiceLocatorPass())->process($container);

        $argument = $container->getDefinition(CrudSurfaceServiceLocator::class)->getArgument(0);
        self::assertInstanceOf(ServiceLocatorArgument::class, $argument);

        $values = $argument->getValues();
        self::assertArrayHasKey('App\\Service\\Http\\Host\\HostIndexService', $values);
        self::assertArrayHasKey('App\\Vendoring\\Service\\Http\\Vendor\\Attachment\\Document\\VendorAttachmentDocumentIndexService', $values);
        self::assertArrayNotHasKey('App\\Something\\ElseService', $values);
    }

    public function testSkipsAbstractHttpSurfaceDefinitions(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(CrudSurfaceServiceLocator::class, new Definition(CrudSurfaceServiceLocator::class));

        $abstract = new Definition('App\\Vendoring\\Service\\Http\\Vendor\\AbstractVendorCrudRouteService');
        $abstract->setAbstract(true);
        $container->setDefinition('App\\Vendoring\\Service\\Http\\Vendor\\AbstractVendorCrudRouteService', $abstract);

        (new CrudSurfaceServiceLocatorPass())->process($container);

        $argument = $container->getDefinition(CrudSurfaceServiceLocator::class)->getArgument(0);
        self::assertInstanceOf(ServiceLocatorArgument::class, $argument);

        $values = $argument->getValues();
        self::assertArrayNotHasKey('App\\Vendoring\\Service\\Http\\Vendor\\AbstractVendorCrudRouteService', $values);
    }

    public function testCollectsAliasedHttpSurfaceServicesAcrossComponents(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(CrudSurfaceServiceLocator::class, new Definition(CrudSurfaceServiceLocator::class));
        $container->setAlias('App\\Vendoring\\Service\\Http\\Vendor\\Attachment\\Document\\VendorAttachmentDocumentIndexService', 'vendoring.vendor_attachment_document_index_service');

        (new CrudSurfaceServiceLocatorPass())->process($container);

        $argument = $container->getDefinition(CrudSurfaceServiceLocator::class)->getArgument(0);
        self::assertInstanceOf(ServiceLocatorArgument::class, $argument);

        $values = $argument->getValues();
        self::assertArrayHasKey('App\\Vendoring\\Service\\Http\\Vendor\\Attachment\\Document\\VendorAttachmentDocumentIndexService', $values);
    }
}
