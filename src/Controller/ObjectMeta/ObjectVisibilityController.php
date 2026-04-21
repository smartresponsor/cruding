<?php

declare(strict_types=1);

namespace App\Cruding\Controller\ObjectMeta;

use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudMutationGuardInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\Crud\CrudRouteNameResolverInterface;
use App\Cruding\ServiceInterface\ObjectMeta\ObjectVisibilityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ObjectVisibilityController extends AbstractController
{
    public function __construct(
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudObjectFinderInterface $objectFinder,
        private readonly CrudAccessContextBuilderInterface $accessContextBuilder,
        private readonly CrudMutationGuardInterface $mutationGuard,
        private readonly CrudRouteNameResolverInterface $routeNameResolver,
        private readonly ObjectVisibilityManagerInterface $visibilityManager,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $context = $this->contextResolver->resolve($request);
        $object = $this->objectFinder->findOne($context);
        $access = $this->accessContextBuilder->build($context, $object);
        $this->mutationGuard->assertCanEdit($access);

        $transition = (string) $request->attributes->get('transition');
        $this->visibilityManager->apply($object, $transition);

        return $this->redirectToRoute(
            $this->routeNameResolver->resolveShow($context),
            $this->routeNameResolver->parameters($context),
        );
    }
}
