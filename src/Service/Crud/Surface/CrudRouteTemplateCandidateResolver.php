<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Surface;

final readonly class CrudRouteTemplateCandidateResolver
{
    /**
     * @return list<string>
     */
    public function templateCandidates(string $resource, ?string $surfacePath, ?string $surfaceToken): array
    {
        $base = null !== $surfacePath && '' !== $surfacePath ? $resource.'/'.$surfacePath : $resource;
        $candidates = [];

        if (null !== $surfaceToken && '' !== $surfaceToken) {
            $candidates[] = $base.'/'.$surfaceToken.'/index.html.twig';
        }

        $candidates[] = $base.'/index.html.twig';
        $candidates[] = $resource.'/index.html.twig';
        $candidates[] = 'index.html.twig';

        return array_values(array_unique($candidates));
    }
}
