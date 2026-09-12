<?php

declare(strict_types=1);

namespace App\Cruding\Service\Operation;

use App\Cruding\Factory\CrudNotFoundResponseFactory;
use App\Cruding\Factory\Resource\CrudResourceContractFactory;
use App\Cruding\Policy\CrudReservedRouteTokenPolicy;
use App\Cruding\Runner\CrudServiceRunner;
use App\Cruding\ServiceInterface\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\CrudPageDefinitionProviderInterface;
use App\Cruding\ServiceInterface\Operation\CrudShowOperationInterface;
use App\Cruding\ValueObject\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Provides the show operation responsibility within the Cruding component.
 */
final readonly class CrudShowOperation implements CrudShowOperationInterface
{
    public function __construct(
        private CrudContextResolverInterface $contextResolver,
        private CrudObjectFinderInterface $objectFinder,
        private CrudAccessContextBuilderInterface $accessContextBuilder,
        private CrudPageDefinitionProviderInterface $pageDefinitionProvider,
        private CrudResourceContractFactory $viewContractFactory,
        private CrudNotFoundResponseFactory $notFoundResponseFactory,
        private CrudReservedRouteTokenPolicy $reservedRouteTokenPolicy,
        private CrudServiceRunner $entrypointRunner,
    ) {
    }

    /**      * Handles the operation represented by this service.      */
    public function handle(Request $request): Response|CrudResourceContract
    {
        $reservedTokenReason = $this->reservedTokenReason($request);
        if (null !== $reservedTokenReason) {
            return $this->notFoundResponseFactory->create($request, $reservedTokenReason, [
                'token' => (string) $request->attributes->get('slug', ''),
                'reservedviewTokens' => $this->reservedRouteTokenPolicy->viewTokens(),
                'reservedOperationTokens' => $this->reservedRouteTokenPolicy->operationTokens(),
                'interpretation' => 'Classic CRUD show grammar matched, but the identity token is reserved for a business view or CRUD operation; Cruding refuses to treat it as an entity slug.',
            ]);
        }

        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->notFoundResponseFactory->create($request, 'crud_resource_not_found');
        }

        $object = $this->objectFinder->findOne($context);
        if (null === $object) {
            $implicitReason = (string) $request->attributes->get('_crud_implicit_object_reason', 'crud_resource_not_found');
            if ('authentication_required' === $implicitReason) {
                throw new AccessDeniedException('Authentication is required to resolve the current resource.');
            }

            return $this->notFoundResponseFactory->create($request, 'crud_resource_not_found', [
                'implicitResolution' => [
                    'reason' => $implicitReason,
                    'actorClass' => $request->attributes->get('_crud_implicit_actor_class'),
                    'actorId' => $request->attributes->get('_crud_implicit_actor_id'),
                    'repositoryClass' => $request->attributes->get('_crud_implicit_repository_class'),
                ],
            ]);
        }

        $access = $this->accessContextBuilder->build($context, $object);
        if (!$access->canView) {
            throw new AccessDeniedHttpException('You are not allowed to view this object.');
        }

        $entrypointResult = $this->entrypointRunner->tryRun($request, $context, $object);
        if (null !== $entrypointResult) {
            return $entrypointResult;
        }

        return $this->viewContractFactory->create($this->pageDefinitionProvider->provideShow($context, $object), $object);
    }

    private function reservedTokenReason(Request $request): ?string
    {
        if ('show' !== (string) $request->attributes->get('_crud_operation', '')) {
            return null;
        }

        if (!$request->attributes->has('slug')) {
            return null;
        }

        $token = $request->attributes->get('slug');
        if (!is_scalar($token)) {
            return null;
        }

        return $this->reservedRouteTokenPolicy->reasonForIdentityToken((string) $token);
    }
}
