<?php

declare(strict_types=1);

namespace App\Cruding\Contract\Capability;

interface MediaCapableInterface
{
    public function getMedia(): iterable;

    public function addMedia(object $media): void;

    public function removeMedia(object $media): void;
}
