<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\DependencyInjection\Compiler;

use App\Cruding\DependencyInjection\Compiler\CrudResourceServiceLocatorPass;
use App\Cruding\Service\Crud\Resource\CrudResourceServiceLocator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class CrudResourceServiceLocatorPassTest extends TestCase
{
    public function testCollectsCanonicalHostAndComponentServices(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(CrudResourceServiceLocator::class, new Definition(CrudResourceServiceLocator::class));
        $container->setDefinition('App\\Service\\Http\\Host\\HostIndexService', new Definition('App\\Service\\Http\\Host\\HostIndexService'));
        $container->setDefinition('component.document.index', new Definition('App\\Fixture\\Service\\Http\\Document\\DocumentIndexService'));
        $container->setDefinition('App\\Vendoring\\Service\\Runtime\\Profile\\VendorProfileShowService', new Definition('App\\Vendoring\\Service\\Runtime\\Profile\\VendorProfileShowService'));
        $container->setDefinition('App\\Something\\ElseService', new Definition('App\\Something\\ElseService'));
        (new CrudResourceServiceLocatorPass())->process($container);
        $argument = $container->getDefinition(CrudResourceServiceLocator::class)->getArgument(0);
        self::assertInstanceOf(ServiceLocatorArgument::class, $argument);
        $values = $argument->getValues();
        self::assertArrayHasKey('App\\Service\\Http\\Host\\HostIndexService', $values);
        self::assertArrayHasKey('component.document.index', $values);
        self::assertArrayHasKey('App\\Vendoring\\Service\\Runtime\\Profile\\VendorProfileShowService', $values);
        self::assertArrayNotHasKey('App\\Something\\ElseService', $values);
    }

    public function testSkipsAbstractCanonicalService(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(CrudResourceServiceLocator::class, new Definition(CrudResourceServiceLocator::class));
        $definition = new Definition('App\\Fixture\\Service\\Http\\Document\\AbstractDocumentService');
        $definition->setAbstract(true);
        $container->setDefinition('abstract.document', $definition);
        (new CrudResourceServiceLocatorPass())->process($container);
        $argument = $container->getDefinition(CrudResourceServiceLocator::class)->getArgument(0);
        self::assertInstanceOf(ServiceLocatorArgument::class, $argument);
        self::assertArrayNotHasKey('abstract.document', $argument->getValues());
    }
}
