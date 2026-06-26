<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud\Resource;

use App\Cruding\Dto\Resource\CrudResourceRequest;
use App\Cruding\Value\Resource\CrudResourceContract;

/**
 * Producer-side provider for resource-bound view routes.
 *
 * Implementations are located by class-nameEntity convention from the parsed route
 * tokens. They must return the neutral Cruding view contract and must not
 * render Twig, return Symfony Response, or build JsonResponse manually.
 */
interface CrudResourceProviderInterface
{
    public function provide(CrudResourceRequest $request): CrudResourceContract;
}
