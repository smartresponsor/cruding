<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Operation;

use App\Cruding\Factory\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Runner\Crud\CrudServiceRunner;
use App\Cruding\Service\Crud\Surface\CrudSurfaceContractFactory;
use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFactoryInterface;
use App\Cruding\ServiceInterface\Crud\CrudPageDefinitionProviderInterface;
use App\Cruding\ServiceInterface\Crud\CrudRouteNameResolverInterface;
use App\Cruding\ServiceInterface\Crud\Operation\CrudCreateOperationInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class CrudCreateOperation implements CrudCreateOperationInterface
{
    public function __construct(
        private CrudContextResolverInterface $contextResolver,
        private CrudFormHandlerInterface $formHandler,
        private CrudRouteNameResolverInterface $routeNameResolver,
        private CrudAccessContextBuilderInterface $accessContextBuilder,
        private CrudPageDefinitionProviderInterface $pageDefinitionProvider,
        private CrudObjectFactoryInterface $objectFactory,
        private CrudSurfaceContractFactory $surfaceContractFactory,
        private CrudNotFoundResponseFactory $notFoundResponseFactory,
        private CrudIdentifierReader $identifierReader,
        private UrlGeneratorInterface $urlGenerator,
        private CrudServiceRunner $entrypointRunner,
    ) {
    }

    public function handle(Request $request): Response|CrudSurfaceContract
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->notFoundResponseFactory->create($request, 'crud_context_not_found');
        }

        $object = $this->objectFactory->create($context->entityClass);
        $this->accessContextBuilder->build($context, $object);

        $entrypointResult = $this->entrypointRunner->tryRun($request, $context, $object);
        if (null !== $entrypointResult) {
            return $entrypointResult;
        }

        if (null === $context->formTypeClass) {
            return $this->notFoundResponseFactory->create($request, 'crud_resource_not_found');
        }

        $form = $this->formHandler->createAndHandle($context->formTypeClass, $object, $request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->formHandler->persist($object);

            $identifierField = $this->identifierReader->detectField($object);
            $identifierValue = $this->identifierReader->read($object, $identifierField);
            if (null === $identifierValue) {
                return new RedirectResponse($this->urlGenerator->generate(
                    $this->routeNameResolver->resolveIndex($context),
                    $this->routeNameResolver->parameters($context, null, null, 'index'),
                ));
            }

            return new RedirectResponse($this->urlGenerator->generate(
                $this->routeNameResolver->resolveShow($context, $identifierField),
                $this->routeNameResolver->parameters($context, $identifierValue, $identifierField, 'show'),
            ));
        }

        $page = $this->pageDefinitionProvider->provideNew($context, $object, $form->createView());

        return $this->surfaceContractFactory->create($page, $object, $form->createView());
    }
}
