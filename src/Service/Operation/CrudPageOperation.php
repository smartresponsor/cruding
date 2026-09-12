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
use App\Cruding\ServiceInterface\Operation\CrudPageOperationInterface;
use App\Cruding\ValueObject\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides the page operation responsibility within the Cruding component.
 */
final readonly class CrudPageOperation implements CrudPageOperationInterface
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
            $slug = $request->attributes->get('slug', '');

            return $this->notFoundResponseFactory->create($request, $reservedTokenReason, [
                'token' => is_scalar($slug) ? (string) $slug : '',
                'reservedViewTokens' => $this->reservedRouteTokenPolicy->viewTokens(),
                'reservedOperationTokens' => $this->reservedRouteTokenPolicy->operationTokens(),
                'interpretation' => 'Classic CRUD page grammar matched, but the identity token is reserved for a business view or CRUD operation; Cruding refuses to treat it as an entity slug.',
            ]);
        }

        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->notFoundResponseFactory->create($request, 'crud_resource_not_found');
        }

        $object = $this->objectFinder->findOne($context);
        if (null === $object) {
            if ($request->attributes->has('id') || $request->attributes->has('slug')) {
                return $this->notFoundResponseFactory->create($request, 'crud_resource_not_found');
            }

            $access = $this->accessContextBuilder->build($context);
            if (!$access->canView) {
                throw new AccessDeniedHttpException('You are not allowed to view this resource page.');
            }

            return $this->viewContractFactory->create($this->pageDefinitionProvider->providePage($context));
        }

        $access = $this->accessContextBuilder->build($context, $object);
        if (!$access->canView) {
            throw new AccessDeniedHttpException('You are not allowed to view this object page.');
        }

        $entrypointResult = $this->entrypointRunner->tryRun($request, $context, $object);
        if (null !== $entrypointResult) {
            return $entrypointResult;
        }

        return $this->viewContractFactory->create($this->pageDefinitionProvider->providePage($context, $object), $object);
    }

    private function reservedTokenReason(Request $request): ?string
    {
        $operation = $request->attributes->get('_crud_operation', '');
        if (!is_scalar($operation) || 'page' !== (string) $operation) {
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
