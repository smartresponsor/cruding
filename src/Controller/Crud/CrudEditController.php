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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CrudEditController extends AbstractController
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
        $this->mutationGuard->assertCanEdit($access);

        if (null === $context->formTypeClass) {
            throw new NotFoundHttpException(sprintf('Form type for "%s" could not be resolved.', $context->resourcePath));
        }

        $form = $this->formHandler->createAndHandle($this, $context->formTypeClass, $object, $request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->formHandler->flush($object);

            return $this->redirectToRoute(
                $this->routeNameResolver->resolveShow($context),
                $this->routeNameResolver->parameters($context),
            );
        }

        return $this->render($context->template('edit'), [
            'crud' => $context,
            'crud_access' => $access,
            'object' => $object,
            'form' => $form->createView(),
        ]);
    }
}
