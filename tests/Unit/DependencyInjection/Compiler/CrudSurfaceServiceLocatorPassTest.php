<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\DependencyInjection\Compiler;

use App\Cruding\DependencyInjection\Compiler\CrudSurfaceServiceLocatorPass;
use App\Cruding\Service\Crud\Surface\CrudSurfaceServiceLocator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class CrudSurfaceServiceLocatorPassTest extends TestCase
{
    public function testCollectsCanonicalHostAndComponentServices(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(CrudSurfaceServiceLocator::class, new Definition(CrudSurfaceServiceLocator::class));
        $container->setDefinition('App\\Service\\Http\\Host\\HostIndexService', new Definition('App\\Service\\Http\\Host\\HostIndexService'));
        $container->setDefinition('component.document.index', new Definition('App\\Fixture\\Service\\Http\\Document\\DocumentIndexService'));
        $container->setDefinition('App\\Something\\ElseService', new Definition('App\\Something\\ElseService'));
        (new CrudSurfaceServiceLocatorPass())->process($container);
        $argument = $container->getDefinition(CrudSurfaceServiceLocator::class)->getArgument(0);
        self::assertInstanceOf(ServiceLocatorArgument::class, $argument);
        $values = $argument->getValues();
        self::assertArrayHasKey('App\\Service\\Http\\Host\\HostIndexService', $values);
        self::assertArrayHasKey('component.document.index', $values);
        self::assertArrayNotHasKey('App\\Something\\ElseService', $values);
    }

    public function testSkipsAbstractCanonicalService(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(CrudSurfaceServiceLocator::class, new Definition(CrudSurfaceServiceLocator::class));
        $definition = new Definition('App\\Fixture\\Service\\Http\\Document\\AbstractDocumentService');
        $definition->setAbstract(true);
        $container->setDefinition('abstract.document', $definition);
        (new CrudSurfaceServiceLocatorPass())->process($container);
        $argument = $container->getDefinition(CrudSurfaceServiceLocator::class)->getArgument(0);
        self::assertInstanceOf(ServiceLocatorArgument::class, $argument);
        self::assertArrayNotHasKey('abstract.document', $argument->getValues());
    }
}
