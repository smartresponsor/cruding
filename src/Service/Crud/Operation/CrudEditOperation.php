<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Operation;

use App\Cruding\Factory\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Runner\Crud\CrudServiceRunner;
use App\Cruding\Service\Crud\Resource\CrudResourceContractFactory;
use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use App\Cruding\ServiceInterface\Crud\CrudMutationGuardInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\Crud\CrudPageDefinitionProviderInterface;
use App\Cruding\ServiceInterface\Crud\CrudRouteNameResolverInterface;
use App\Cruding\ServiceInterface\Crud\Operation\CrudEditOperationInterface;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class CrudEditOperation implements CrudEditOperationInterface
{
    public function __construct(
        private CrudContextResolverInterface $contextResolver,
        private CrudObjectFinderInterface $objectFinder,
        private CrudFormHandlerInterface $formHandler,
        private CrudRouteNameResolverInterface $routeNameResolver,
        private CrudAccessContextBuilderInterface $accessContextBuilder,
        private CrudPageDefinitionProviderInterface $pageDefinitionProvider,
        private CrudMutationGuardInterface $mutationGuard,
        private CrudResourceContractFactory $viewContractFactory,
        private CrudNotFoundResponseFactory $notFoundResponseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private CrudServiceRunner $entrypointRunner,
    ) {
    }

    public function handle(Request $request): Response|CrudResourceContract
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->notFoundResponseFactory->create($request, 'crud_context_not_found');
        }

        $object = $this->objectFinder->findOne($context);
        if (null === $object) {
            return $this->notFoundResponseFactory->create($request, 'crud_resource_not_found');
        }

        $access = $this->accessContextBuilder->build($context, $object);
        $this->mutationGuard->assertCanEdit($access);

        $entrypointResult = $this->entrypointRunner->tryRun($request, $context, $object);
        if (null !== $entrypointResult) {
            return $entrypointResult;
        }

        if (null === $context->formTypeClass) {
            return $this->notFoundResponseFactory->create($request, 'crud_resource_not_found');
        }

        $form = $this->formHandler->createAndHandle($context->formTypeClass, $object, $request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->formHandler->flush($object);

            return new RedirectResponse($this->urlGenerator->generate(
                $this->routeNameResolver->resolveShow($context),
                $this->routeNameResolver->parameters($context, null, null, 'show'),
            ));
        }

        $page = $this->pageDefinitionProvider->provideEdit($context, $object, $form->createView());

        return $this->viewContractFactory->create($page, $object, $form->createView());
    }
}
