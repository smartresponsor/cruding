<?php

declare(strict_types=1);

namespace App\Cruding\Service\Resource;

use App\Cruding\DTO\Resource\CrudResourceRequestDTO;
use App\Cruding\ServiceInterface\Resource\CrudResourceProviderInterface;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the resource service invoker responsibility within the Cruding component.
 */
final readonly class CrudResourceServiceInvoker
{
    /**      * Executes the invoke operation.      */
    public function invoke(object $service, CrudResourceRequestDTO $request): Response|CrudResourceContract
    {
        $result = match (true) {
            $service instanceof CrudResourceProviderInterface => $service->provide($request),
            is_callable($service) => $service($request),
            method_exists($service, 'provide') => $service->provide($request),
            method_exists($service, 'handle') => $service->handle($request),
            default => throw new \LogicException(sprintf('Unsupported view service: %s.', $service::class)),
        };

        if ($result instanceof Response || $result instanceof CrudResourceContract) {
            return $result;
        }

        throw new \LogicException(sprintf('Invalid view result from %s: %s.', $service::class, get_debug_type($result)));
    }
}
