<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Relation;

use App\Cruding\Dto\Relation\ObjectRelationContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

interface ObjectRelationResponderInterface
{
    /** @param list<object> $relations */
    public function htmlList(ObjectRelationContext $context, object $subject, array $relations): Response;

    /** @param list<object> $relations */
    public function apiList(ObjectRelationContext $context, object $subject, array $relations): Response;

    public function apiAttached(ObjectRelationContext $context, object $subject, object $relation): Response;

    public function apiDetached(ObjectRelationContext $context, object $subject): Response;

    public function notFound(string $resourcePath, string $detail = 'Resource not found.'): JsonResponse;
}
