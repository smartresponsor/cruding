<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Relation;

use App\Cruding\Dto\Relation\ObjectRelationContext;
use Symfony\Component\HttpFoundation\Request;

interface ObjectRelationContextResolverInterface
{
    public function resolve(Request $request): ObjectRelationContext;
}
