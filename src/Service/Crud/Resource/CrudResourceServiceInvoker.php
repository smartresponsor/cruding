<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

use App\Cruding\Dto\Resource\CrudResourceRequest;
use App\Cruding\ServiceInterface\Crud\Resource\CrudResourceProviderInterface;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Response;

final readonly class CrudResourceServiceInvoker
{
    public function invoke(object $service, CrudResourceRequest $request): Response|CrudResourceContract
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
