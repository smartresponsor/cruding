<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Surface;

use App\Cruding\Dto\Surface\CrudSurfaceRequest;
use App\Cruding\Value\Surface\CrudSurfaceContract;

/**
 * Producer-side provider for resource-bound surface routes.
 *
 * Implementations are located by class-nameEntity convention from the parsed route
 * tokens. They must return the neutral Cruding surface contract and must not
 * render Twig, return Symfony Response, or build JsonResponse manually.
 */
interface CrudSurfaceProviderInterface
{
    public function provide(CrudSurfaceRequest $request): CrudSurfaceContract;
}
