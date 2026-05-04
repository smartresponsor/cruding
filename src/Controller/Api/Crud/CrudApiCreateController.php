<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Api\Crud;

use App\Cruding\ServiceInterface\Crud\CrudApiInputHandlerInterface;
use App\Cruding\ServiceInterface\Crud\CrudApiResponderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CrudApiCreateController extends AbstractController
{
    public function __construct(
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudApiInputHandlerInterface $apiInputHandler,
        private readonly CrudFormHandlerInterface $formHandler,
        private readonly CrudApiResponderInterface $apiResponder,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->apiResponder->notFound((string) $request->attributes->get('resourcePath', ''));
        }

        $object = $this->createEmptyObject($context->entityClass);

        if (null === $context->formTypeClass) {
            return $this->apiResponder->notFound($context->resourcePath, sprintf('Form type for "%s" could not be resolved.', $context->resourcePath));
        }

        $form = $this->apiInputHandler->submit($context->formTypeClass, $object, $request, true);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->apiResponder->validationError($context, $form);
        }

        $this->formHandler->persist($object);

        return $this->apiResponder->item($context, $object, JsonResponse::HTTP_CREATED);
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
