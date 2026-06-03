<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Crud;

use App\Cruding\Service\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Service\Crud\CrudSurfaceContractFactory;
use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use App\Cruding\ServiceInterface\Crud\CrudPageDefinitionProviderInterface;
use App\Cruding\ServiceInterface\Crud\CrudRouteNameResolverInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CrudCreateController extends AbstractController
{
    public function __construct(
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudFormHandlerInterface $formHandler,
        private readonly CrudRouteNameResolverInterface $routeNameResolver,
        private readonly CrudAccessContextBuilderInterface $accessContextBuilder,
        private readonly CrudPageDefinitionProviderInterface $pageDefinitionProvider,
        private readonly CrudSurfaceContractFactory $surfaceContractFactory,
        private readonly CrudNotFoundResponseFactory $notFoundResponseFactory,
    ) {
    }

    public function __invoke(Request $request): Response|CrudSurfaceContract
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->notFoundResponseFactory->create($request, 'crud_context_not_found');
        }

        $object = $this->createEmptyObject($context->entityClass);
        $access = $this->accessContextBuilder->build($context, $object);

        if (null === $context->formTypeClass) {
            return $this->notFoundResponseFactory->create($request, 'crud_resource_not_found');
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

        $page = $this->pageDefinitionProvider->provideNew($context, $object, $form->createView());
        $surface = $this->surfaceContractFactory->create($page, $object, $form->createView());

        return $surface;
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

    private function createEmptyObject(string $entityClass): object
    {
        $reflection = new \ReflectionClass($entityClass);
        $constructor = $reflection->getConstructor();
        if (null === $constructor) {
            return $reflection->newInstance();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            $type = $parameter->getType();
            if ($type instanceof \ReflectionNamedType) {
                if ($type->allowsNull()) {
                    $arguments[] = null;
                    continue;
                }

                $arguments[] = match ($type->getName()) {
                    'string' => '',
                    'int' => 0,
                    'float' => 0.0,
                    'bool' => false,
                    'array' => [],
                    default => null,
                };
                continue;
            }

            $arguments[] = null;
        }

        try {
            return $reflection->newInstanceArgs($arguments);
        } catch (\Throwable) {
            return $reflection->newInstanceWithoutConstructor();
        }
    }
}
