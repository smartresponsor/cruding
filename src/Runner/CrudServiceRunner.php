<?php

declare(strict_types=1);

namespace App\Cruding\Runner;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\DTO\Entrypoint\CrudServiceContextDTO;
use App\Cruding\DTO\Entrypoint\CrudServiceResultDTO;
use App\Cruding\Invoker\CrudServiceInvoker;
use App\Cruding\Resolver\CrudServiceResolver;
use App\Cruding\ValueObject\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the service runner responsibility within the Cruding component.
 */
final readonly class CrudServiceRunner
{
    public function __construct(
        private CrudServiceResolver $resolver,
        private CrudServiceInvoker $invoker,
    ) {
    }

    /**      * Runs the selected Cruding workflow.      */
    public function run(Request $request, CrudContextDTO $crudContext, ?object $object = null): CrudServiceResultDTO
    {
        $resolutionStartedAt = hrtime(true);
        $resolution = $this->resolver->resolve($request, $crudContext);
        $request->attributes->set('_crud_service_resolution_ms', number_format((hrtime(true) - $resolutionStartedAt) / 1_000_000, 2, '.', ''));

        $request->attributes->set('_crud_service_class', $resolution->service::class);

        $context = new CrudServiceContextDTO($request, $crudContext, $object);
        $invocationStartedAt = hrtime(true);
        $result = $this->invoker->invoke(
            $resolution->service,
            $context,
            $resolution->diagnostics(),
        );
        $request->attributes->set('_crud_service_invocation_ms', number_format((hrtime(true) - $invocationStartedAt) / 1_000_000, 2, '.', ''));

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
                'actorGrounded' => $context->isActorGrounded(),
                'actorIdentityField' => $context->actorIdentityField(),
                'actorIdentityValue' => $context->actorIdentityValue(),
                'actorAdminIdentityField' => $context->actorAdminIdentityField(),
                'actorAdminIdentityValue' => $context->actorAdminIdentityValue(),
                'serviceResolution' => $resolution->diagnostics(),
            ],
        ]);
    }

    /**      * Attempts to run the selected Cruding workflow without forcing a result.      */
    public function tryRun(Request $request, CrudContextDTO $crudContext, ?object $object = null): Response|CrudResourceContract|null
    {
        $result = $this->run($request, $crudContext, $object);

        if ($result->hasPayload()) {
            return $result->payload();
        }

        return null;
    }
}
