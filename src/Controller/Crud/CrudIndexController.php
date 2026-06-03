<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Crud;

use App\Cruding\Service\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Service\Crud\CrudSurfaceContractFactory;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudPageDefinitionProviderInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CrudIndexController extends AbstractController
{
    public function __construct(
        private readonly CrudContextResolverInterface $contextResolver,
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

        $page = $this->pageDefinitionProvider->provideIndex($context);
        $surface = $this->surfaceContractFactory->create($page);

        return $surface;
    }
}
