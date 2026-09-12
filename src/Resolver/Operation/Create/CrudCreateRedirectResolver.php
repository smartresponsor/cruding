<?php

declare(strict_types=1);

namespace App\Cruding\Resolver\Operation\Create;

use App\Cruding\Service\Operation\Create\CrudCreateWorkItem;
use App\Cruding\Service\Operation\CrudIdentifierReader;
use App\Cruding\ServiceInterface\CrudRouteNameResolverInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Resolves create redirect resolver for Cruding request processing.
 */
final readonly class CrudCreateRedirectResolver
{
    public function __construct(
        private CrudIdentifierReader $identifierReader,
        private CrudRouteNameResolverInterface $routeNameResolver,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**      * Executes the after create operation.      */
    public function afterCreate(CrudCreateWorkItem $workItem): RedirectResponse
    {
        $identifierField = $this->identifierReader->detectField($workItem->object);
        $identifierValue = $this->identifierReader->read($workItem->object, $identifierField);
        if (null === $identifierValue) {
            return new RedirectResponse($this->urlGenerator->generate(
                $this->routeNameResolver->resolveIndex($workItem->context),
                $this->routeNameResolver->parameters($workItem->context, null, null, 'index'),
            ));
        }

        return new RedirectResponse($this->urlGenerator->generate(
            $this->routeNameResolver->resolveShow($workItem->context, $identifierField),
            $this->routeNameResolver->parameters($workItem->context, $identifierValue, $identifierField, 'show'),
        ));
    }
}
