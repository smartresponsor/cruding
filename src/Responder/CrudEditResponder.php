<?php

declare(strict_types=1);

namespace App\Cruding\Responder;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\Factory\Resource\CrudResourceContractFactory;
use App\Cruding\ServiceInterface\CrudPageDefinitionProviderInterface;
use App\Cruding\ServiceInterface\CrudRouteNameResolverInterface;
use App\Cruding\ValueObject\Resource\CrudResourceContract;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Produces edit-operation redirect and resource-view responses.
 */
final readonly class CrudEditResponder
{
    public function __construct(
        private CrudRouteNameResolverInterface $routeNameResolver,
        private CrudPageDefinitionProviderInterface $pageDefinitionProvider,
        private CrudResourceContractFactory $viewContractFactory,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /** Returns the canonical show redirect after a successful edit. */
    public function redirectToShow(CrudContextDTO $context): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate(
            $this->routeNameResolver->resolveShow($context),
            $this->routeNameResolver->parameters($context, null, null, 'show'),
        ));
    }

    /** Builds the resource contract used to render the edit form. */
    public function resource(CrudContextDTO $context, object $object, FormView $formView): CrudResourceContract
    {
        $page = $this->pageDefinitionProvider->provideEdit($context, $object, $formView);

        return $this->viewContractFactory->create($page, $object, $formView);
    }
}
