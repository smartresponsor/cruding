<?php

declare(strict_types=1);

namespace App\Cruding\Service\Operation;

use App\Cruding\ServiceInterface\CrudApiResponderInterface;
use App\Cruding\ServiceInterface\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\Operation\CrudApiShowOperationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the api show operation responsibility within the Cruding component.
 */
final readonly class CrudApiShowOperation implements CrudApiShowOperationInterface
{
    public function __construct(
        private CrudContextResolverInterface $contextResolver,
        private CrudObjectFinderInterface $objectFinder,
        private CrudApiResponderInterface $apiResponder,
    ) {
    }

    /**      * Handles the operation represented by this service.      */
    public function handle(Request $request): Response
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->apiResponder->notFound((string) $request->attributes->get('resourcePath', ''));
        }

        $object = $this->objectFinder->findOne($context);
        if (null === $object) {
            return $this->apiResponder->notFound($context->resourcePath, sprintf(
                'Object for resource "%s" was not found by %s "%s".',
                $context->resourcePath,
                $context->identifierField,
                (string) $context->identifierValue,
            ));
        }

        return $this->apiResponder->item($context, $object);
    }
}
