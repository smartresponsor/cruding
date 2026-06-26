<?php

declare(strict_types=1);

namespace App\Cruding\Runner\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudServiceContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudServiceResult;
use App\Cruding\Invoker\Crud\CrudServiceInvoker;
use App\Cruding\Resolver\Crud\CrudServiceResolver;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class CrudServiceRunner
{
    public function __construct(
        private CrudServiceResolver $resolver,
        private CrudServiceInvoker $invoker,
    ) {
    }

    public function run(Request $request, CrudContext $crudContext, ?object $object = null): CrudServiceResult
    {
        $resolution = $this->resolver->resolve($request, $crudContext);
        $context = new CrudServiceContext($request, $crudContext, $object);
        $result = $this->invoker->invoke(
            $resolution->service,
            $context,
            $resolution->diagnostics(),
        );

        return $result->withDiagnostics([
            'entrypointTrace' => [
                'status' => $result->status,
                'continueDefault' => $result->shouldContinueDefault(),
                'hasPayload' => $result->hasPayload(),
                'httpMethod' => $context->httpMethod(),
                'routeName' => $context->routeName(),
                'path' => $context->path(),
                'resourcePath' => $context->resourcePath(),
                'operation' => $context->operation(),
                'actorScope' => $context->actorScope(),
                'actorScoped' => $context->isActorScoped(),
                'actorGrounded' => $context->isActorGrounded(),
                'actorIdentityField' => $context->actorIdentityField(),
                'actorIdentityValue' => $context->actorIdentityValue(),
                'actorAdminIdentityField' => $context->actorAdminIdentityField(),
                'actorAdminIdentityValue' => $context->actorAdminIdentityValue(),
                'serviceResolution' => $resolution->diagnostics(),
            ],
        ]);
    }

    public function tryRun(Request $request, CrudContext $crudContext, ?object $object = null): Response|CrudResourceContract|null
    {
        $result = $this->run($request, $crudContext, $object);

        if ($result->hasPayload()) {
            return $result->payload();
        }

        return null;
    }
}
