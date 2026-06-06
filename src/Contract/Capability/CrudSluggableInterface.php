<?php

declare(strict_types=1);

namespace App\Cruding\Contract\Capability;

interface CrudSluggableInterface
{
    public function getSlug(): string;
}
