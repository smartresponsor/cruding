<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Crud;

use App\Cruding\Service\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Service\Crud\CrudSurfaceContractFactory;
use App\Cruding\Service\Crud\CrudSurfaceResponseFactory;
use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\Crud\CrudPageDefinitionProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CrudShowController extends AbstractController
{
    public function __construct(
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudObjectFinderInterface $objectFinder,
        private readonly CrudAccessContextBuilderInterface $accessContextBuilder,
        private readonly CrudPageDefinitionProviderInterface $pageDefinitionProvider,
        private readonly CrudSurfaceContractFactory $surfaceContractFactory,
        private readonly CrudSurfaceResponseFactory $surfaceResponseFactory,
        private readonly CrudNotFoundResponseFactory $notFoundResponseFactory,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->notFoundResponseFactory->create($request, 'crud_resource_not_found');
        }

        $object = $this->objectFinder->findOne($context);
        if (null === $object) {
            return $this->notFoundResponseFactory->create($request, 'crud_resource_not_found');
        }

        $access = $this->accessContextBuilder->build($context, $object);
        if (!$access->canView) {
            throw $this->createAccessDeniedException('You are not allowed to view this object.');
        }

        $page = $this->pageDefinitionProvider->provideShow($context, $object);
        $surface = $this->surfaceContractFactory->create($page, $object);

        return $this->surfaceResponseFactory->render($surface);
    }
}
