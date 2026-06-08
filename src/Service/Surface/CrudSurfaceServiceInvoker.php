<?php

declare(strict_types=1);

namespace App\Cruding\Service\Surface;

use App\Cruding\Dto\Surface\CrudSurfaceRequest;
use App\Cruding\ServiceInterface\Surface\CrudSurfaceProviderInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Calls canonical HTTP services resolved from provider keys by FQCN convention.
 */
final readonly class CrudSurfaceServiceInvoker
{
    public function invoke(object $service, CrudSurfaceRequest $request): Response|CrudSurfaceContract
    {
        $result = null;
        $called = false;

        if ($service instanceof CrudSurfaceProviderInterface) {
            $result = $service->provide($request);
            $called = true;
        } elseif (is_callable($service)) {
            $result = $service($request);
            $called = true;
        } elseif (method_exists($service, 'provide')) {
            $result = $service->provide($request);
            $called = true;
        } elseif (method_exists($service, 'handle')) {
            $result = $service->handle($request);
            $called = true;
        }

        if (!$called) {
            throw new \LogicException(sprintf('Surface service "%s" must be invokable, implement CrudSurfaceProviderInterface, or expose provide()/handle().', $service::class));
        }

        if ($result instanceof Response || $result instanceof CrudSurfaceContract) {
            return $result;
        }

        throw new \LogicException(sprintf('Surface service "%s" must return %s or %s; got %s.', $service::class, Response::class, CrudSurfaceContract::class, get_debug_type($result)));
    }
}
