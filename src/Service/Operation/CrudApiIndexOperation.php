<?php

declare(strict_types=1);

namespace App\Cruding\Service\Operation;

use App\Cruding\ServiceInterface\CrudApiResponderInterface;
use App\Cruding\ServiceInterface\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\Operation\CrudApiIndexOperationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the api index operation responsibility within the Cruding component.
 */
final readonly class CrudApiIndexOperation implements CrudApiIndexOperationInterface
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
            $resourcePath = $request->attributes->get('resourcePath', '');

            return $this->apiResponder->notFound(is_scalar($resourcePath) ? (string) $resourcePath : '');
        }

        return $this->apiResponder->collection($context, $this->objectFinder->findAll($context));
    }
}
