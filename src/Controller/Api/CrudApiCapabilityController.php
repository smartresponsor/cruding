<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Api;

use App\Cruding\Factory\Api\CrudApiProblemResponseFactory;
use App\Cruding\Provider\Api\CrudApiResourceCapabilityProvider;
use App\Cruding\Service\Runtime\CrudRuntimeRouteGuard;
use App\Cruding\ServiceInterface\CrudContextResolverInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
/**
 * Exposes stable machine-readable CRUD capability metadata for one runtime resource.
 */
final readonly class CrudApiCapabilityController
{
    public function __construct(
        private CrudContextResolverInterface $contextResolver,
        private CrudApiResourceCapabilityProvider $capabilityProvider,
        private CrudApiProblemResponseFactory $problemResponseFactory,
        private CrudRuntimeRouteGuard $runtimeRouteGuard,
    ) {
    }

    /**
     * Resolves the requested resource and returns its bounded CRUD capability contract.
     */
    public function __invoke(Request $request): Response
    {
        $resourcePath = $request->attributes->get('resourcePath');
        if (!is_string($resourcePath) || '' === trim($resourcePath)) {
            return $this->problemResponseFactory->notFound('CRUD capability resource could not be resolved.', [
                'code' => 'crud_capability_resource_not_found',
            ]);
        }

        if (!$this->runtimeRouteGuard->allowsResourcePath($resourcePath)) {
            return $this->problemResponseFactory->notFound('CRUD resource is not available in the active runtime.', [
                'code' => 'crud_runtime_resource_not_allowed',
                'resourcePath' => $resourcePath,
            ]);
        }

        $request->attributes->set('_crud_operation', 'index');
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->problemResponseFactory->notFound('CRUD capability resource could not be resolved.', [
                'code' => 'crud_capability_resource_not_found',
                'resourcePath' => $resourcePath,
            ]);
        }

        return new JsonResponse($this->capabilityProvider->provide($context)->toArray());
    }
}
