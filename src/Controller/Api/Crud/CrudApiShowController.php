<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Api\Crud;

use App\Cruding\ServiceInterface\Crud\CrudApiResponderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CrudApiShowController extends AbstractController
{
    public function __construct(
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudObjectFinderInterface $objectFinder,
        private readonly CrudApiResponderInterface $apiResponder,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $context = $this->contextResolver->resolve($request);
        $object = $this->objectFinder->findOne($context);

        return $this->apiResponder->item($context, $object);
    }
}
