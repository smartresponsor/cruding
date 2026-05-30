<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\ServiceInterface\Crud\CrudTemplateResolverInterface;
use Twig\Environment;

final class CrudTemplateResolver implements CrudTemplateResolverInterface
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function resolvePrefix(string $resourcePath): string
    {
        $resourcePath = trim(str_replace('_', '-', $resourcePath), '/');

        if ('' !== $resourcePath && $this->templateExists('@Interfacing/'.$resourcePath.'/index.html.twig')) {
            return '@Interfacing/'.$resourcePath;
        }

        return '@Cruding/crud';
    }

    public function resolveSurfaceTemplate(string $resourcePath): string
    {
        $resourcePath = trim(str_replace('_', '-', $resourcePath), '/');

        if ('' !== $resourcePath && $this->templateExists('@Interfacing/'.$resourcePath.'/index.html.twig')) {
            return '@Interfacing/'.$resourcePath.'/index.html.twig';
        }

        if ($this->templateExists('@Interfacing/base.html.twig')) {
            return '@Interfacing/base.html.twig';
        }

        return '@Cruding/crud/index.html.twig';
    }

    private function templateExists(string $template): bool
    {
        $loader = $this->twig->getLoader();

        return method_exists($loader, 'exists') && $loader->exists($template);
    }
}
