<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

final readonly class CrudRouteTemplateCandidateResolver
{
    /**
     * @return list<string>
     */
    public function templateCandidates(string $resource, ?string $viewPath, ?string $ViewToken): array
    {
        $base = null !== $viewPath && '' !== $viewPath ? $resource.'/'.$viewPath : $resource;
        $candidates = [];

        if (null !== $ViewToken && '' !== $ViewToken) {
            $candidates[] = $base.'/'.$ViewToken.'/index.html.twig';
        }

        $candidates[] = $base.'/index.html.twig';
        $candidates[] = $resource.'/index.html.twig';
        $candidates[] = 'index.html.twig';

        return array_values(array_unique($candidates));
    }
}
