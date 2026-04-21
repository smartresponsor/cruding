<?php

declare(strict_types=1);

namespace App\Cruding\Contract\Capability;

interface SluggableInterface
{
    public function getSlug(): string;
}
