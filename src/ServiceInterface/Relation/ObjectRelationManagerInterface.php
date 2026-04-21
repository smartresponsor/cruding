<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Relation;

use App\Cruding\Dto\Relation\ObjectRelationContext;

interface ObjectRelationManagerInterface
{
    /** @return list<object> */
    public function list(ObjectRelationContext $context, object $subject): array;

    public function attach(ObjectRelationContext $context, object $subject): object;

    public function detach(ObjectRelationContext $context, object $subject): void;
}
