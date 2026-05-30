<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Crud;

use App\Cruding\Service\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Service\Crud\CrudSurfaceContractFactory;
use App\Cruding\Service\Crud\CrudSurfaceResponseFactory;
use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use App\Cruding\ServiceInterface\Crud\CrudMutationGuardInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\Crud\CrudPageDefinitionProviderInterface;
use App\Cruding\ServiceInterface\Crud\CrudRouteNameResolverInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CrudEditController extends AbstractController
{
    public function __construct(
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudObjectFinderInterface $objectFinder,
        private readonly CrudFormHandlerInterface $formHandler,
        private readonly CrudRouteNameResolverInterface $routeNameResolver,
        private readonly CrudAccessContextBuilderInterface $accessContextBuilder,
        private readonly CrudPageDefinitionProviderInterface $pageDefinitionProvider,
        private readonly CrudMutationGuardInterface $mutationGuard,
        private readonly CrudSurfaceContractFactory $surfaceContractFactory,
        private readonly CrudSurfaceResponseFactory $surfaceResponseFactory,
        private readonly CrudNotFoundResponseFactory $notFoundResponseFactory,
    ) {
    }

    public function __invoke(Request $request): Response
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

        if (null === $context->formTypeClass) {
            return $this->notFoundResponseFactory->create($request, 'crud_resource_not_found');
        }

        $form = $this->formHandler->createAndHandle($this, $context->formTypeClass, $object, $request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->formHandler->flush($object);

            return $this->redirectToRoute(
                $this->routeNameResolver->resolveShow($context),
                $this->routeNameResolver->parameters($context),
            );
        }

        $page = $this->pageDefinitionProvider->provideEdit($context, $object, $form->createView());
        $surface = $this->surfaceContractFactory->create($page, $object, $form->createView());

        return $this->surfaceResponseFactory->render($surface);
    }
}
