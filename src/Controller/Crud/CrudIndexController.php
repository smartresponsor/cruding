<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Crud;

use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CrudIndexController extends AbstractController
{
    public function __construct(
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudObjectFinderInterface $objectFinder,
        private readonly CrudAccessContextBuilderInterface $accessContextBuilder,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $context = $this->contextResolver->resolve($request);

        return $this->render($context->template('index'), [
            'crud' => $context,
            'crud_access' => $this->accessContextBuilder->build($context),
            'objects' => $this->objectFinder->findAll($context),
        ]);
    }
}
