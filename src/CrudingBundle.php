<?php

declare(strict_types=1);

namespace App\Cruding;

use App\Cruding\DependencyInjection\Compiler\CrudResourceServiceLocatorPass;
use App\Cruding\DependencyInjection\CrudingExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class CrudingBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CrudResourceServiceLocatorPass());
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return parent::getContainerExtension() ?? new CrudingExtension();
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
