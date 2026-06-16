<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Surface;

use App\Cruding\Dto\Surface\CrudSurfaceRequest;
use App\Cruding\ServiceInterface\Surface\CrudSurfaceProviderInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Component\HttpFoundation\Response;

final readonly class CrudSurfaceServiceInvoker
{
    public function invoke(object $service, CrudSurfaceRequest $request): Response|CrudSurfaceContract
    {
        $result = match (true) {
            $service instanceof CrudSurfaceProviderInterface => $service->provide($request),
            is_callable($service) => $service($request),
            method_exists($service, 'provide') => $service->provide($request),
            method_exists($service, 'handle') => $service->handle($request),
            default => throw new \LogicException(sprintf('Unsupported surface service: %s.', $service::class)),
        };

        if ($result instanceof Response || $result instanceof CrudSurfaceContract) {
            return $result;
        }

        throw new \LogicException(sprintf('Invalid surface result from %s: %s.', $service::class, get_debug_type($result)));
    }
}
