<?php

declare(strict_types=1);

namespace App\Cruding\Contract\Capability;

interface TaggableInterface
{
    public function getTags(): iterable;

    public function addTag(object $tag): void;

    public function removeTag(object $tag): void;
}
