<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Crud;

use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudInterfacingProviderSurfaceBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudPageDefinitionProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CrudIndexController extends AbstractController
{
    public function __construct(
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudPageDefinitionProviderInterface $pageDefinitionProvider,
        private readonly CrudInterfacingProviderSurfaceBuilderInterface $providerSurfaceBuilder,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $page = $this->pageDefinitionProvider->provideIndex($context);

        return $this->render(
            'interfacing/bridge/provider_surface.html.twig',
            $this->providerSurfaceBuilder->build($page),
        );
    }
}
