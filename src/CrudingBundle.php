<?php

declare(strict_types=1);

namespace App\Cruding;

use App\Cruding\DependencyInjection\Compiler\CrudResourceServiceLocatorPass;
use App\Cruding\DependencyInjection\CrudingExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Provides the ing bundle responsibility within the Cruding component.
 */
final class CrudingBundle extends Bundle
{
    /**      * Executes the build operation.      */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CrudResourceServiceLocatorPass());
    }

    /**      * Returns container extension.      */
    public function getContainerExtension(): ?ExtensionInterface
    {
        return parent::getContainerExtension() ?? new CrudingExtension();
    }

    /**      * Returns path.      */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
