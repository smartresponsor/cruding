<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Crud;

use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use App\Cruding\ServiceInterface\Crud\CrudMutationGuardInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\Crud\CrudRouteNameResolverInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CrudDeleteController extends AbstractController
{
    public function __construct(
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudObjectFinderInterface $objectFinder,
        private readonly CrudFormHandlerInterface $formHandler,
        private readonly CrudRouteNameResolverInterface $routeNameResolver,
        private readonly CrudAccessContextBuilderInterface $accessContextBuilder,
        private readonly CrudMutationGuardInterface $mutationGuard,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $context = $this->contextResolver->resolve($request);
        $object = $this->objectFinder->findOne($context);
        $access = $this->accessContextBuilder->build($context, $object);
        $this->mutationGuard->assertCanDelete($access);

        if (!$this->isCsrfTokenValid('delete_'.$context->resourcePath.'_'.$context->identifierValue, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $this->formHandler->delete($object);

        return $this->redirectToRoute(
            $this->routeNameResolver->resolveIndex($context),
            $this->routeNameResolver->parameters($context, null),
        );
    }
}
