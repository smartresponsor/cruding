<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Operation;

use App\Cruding\Service\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Service\Crud\CrudReservedRouteTokenPolicy;
use App\Cruding\Service\Crud\Surface\CrudSurfaceContractFactory;
use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\Crud\CrudPageDefinitionProviderInterface;
use App\Cruding\ServiceInterface\Crud\Operation\CrudShowOperationInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class CrudShowOperation implements CrudShowOperationInterface
{
    public function __construct(
        private CrudContextResolverInterface $contextResolver,
        private CrudObjectFinderInterface $objectFinder,
        private CrudAccessContextBuilderInterface $accessContextBuilder,
        private CrudPageDefinitionProviderInterface $pageDefinitionProvider,
        private CrudSurfaceContractFactory $surfaceContractFactory,
        private CrudNotFoundResponseFactory $notFoundResponseFactory,
        private CrudReservedRouteTokenPolicy $reservedRouteTokenPolicy,
    ) {
    }

    public function handle(Request $request): Response|CrudSurfaceContract
    {
        $reservedTokenReason = $this->reservedTokenReason($request);
        if (null !== $reservedTokenReason) {
            return $this->notFoundResponseFactory->create($request, $reservedTokenReason, [
                'token' => (string) $request->attributes->get('slug', ''),
                'reservedSurfaceTokens' => $this->reservedRouteTokenPolicy->surfaceTokens(),
                'reservedOperationTokens' => $this->reservedRouteTokenPolicy->operationTokens(),
                'interpretation' => 'Classic CRUD show grammar matched, but the identity token is reserved for a business surface or CRUD operation; Cruding refuses to treat it as an entity slug.',
            ]);
        }

        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->notFoundResponseFactory->create($request, 'crud_resource_not_found');
        }

        if (!$request->attributes->has('id') && !$request->attributes->has('slug')) {
            return $this->notFoundResponseFactory->create($request, 'crud_resource_not_found', [
                'interpretation' => 'Classic CRUD show route matched, but no identifier was provided; use /{resourcePath}/show/{id} or /{resourcePath}/show/{slug} to target a record.',
            ]);
        }

        $object = $this->objectFinder->findOne($context);
        if (null === $object) {
            return $this->notFoundResponseFactory->create($request, 'crud_resource_not_found');
        }

        $access = $this->accessContextBuilder->build($context, $object);
        if (!$access->canView) {
            throw new AccessDeniedHttpException('You are not allowed to view this object.');
        }

        return $this->surfaceContractFactory->create($this->pageDefinitionProvider->provideShow($context, $object), $object);
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
