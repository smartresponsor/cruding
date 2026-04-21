<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\ObjectMeta;

use App\Cruding\Dto\ObjectMeta\ObjectVisibilityContext;

interface ObjectVisibilityManagerInterface
{
    public function inspect(object $object): ObjectVisibilityContext;

    public function apply(object $object, string $transition): ObjectVisibilityContext;
}
