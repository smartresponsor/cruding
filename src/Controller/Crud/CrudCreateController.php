<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Crud;

use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use App\Cruding\ServiceInterface\Crud\CrudRouteNameResolverInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CrudCreateController extends AbstractController
{
    public function __construct(
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudFormHandlerInterface $formHandler,
        private readonly CrudRouteNameResolverInterface $routeNameResolver,
        private readonly CrudAccessContextBuilderInterface $accessContextBuilder,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $context = $this->contextResolver->resolve($request);
        $entityClass = $context->entityClass;
        $object = new $entityClass();
        $access = $this->accessContextBuilder->build($context, $object);

        if (null === $context->formTypeClass) {
            throw new NotFoundHttpException(sprintf('Form type for "%s" could not be resolved.', $context->resourcePath));
        }

        $form = $this->formHandler->createAndHandle($this, $context->formTypeClass, $object, $request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->formHandler->persist($object);

            $identifierField = $this->detectIdentifierField($object);
            $identifierValue = $this->readIdentifier($object, $identifierField);
            if (null === $identifierValue) {
                return $this->redirectToRoute(
                    $this->routeNameResolver->resolveIndex($context),
                    $this->routeNameResolver->parameters($context, null, null),
                );
            }

            return $this->redirectToRoute(
                $this->routeNameResolver->resolveShow($context, $identifierField),
                $this->routeNameResolver->parameters($context, $identifierValue, $identifierField),
            );
        }

        return $this->render($context->template('new'), [
            'crud' => $context,
            'crud_access' => $access,
            'form' => $form->createView(),
        ]);
    }

    private function detectIdentifierField(object $object): string
    {
        if (method_exists($object, 'getSlug')) {
            return 'slug';
        }

        return 'id';
    }

    private function readIdentifier(object $object, string $field): string|int|null
    {
        $getter = 'get'.ucfirst($field);
        if (method_exists($object, $getter)) {
            $value = $object->{$getter}();

            return is_scalar($value) ? $value : null;
        }

        return null;
    }
}
