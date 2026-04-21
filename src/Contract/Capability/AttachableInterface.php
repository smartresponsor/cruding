<?php

declare(strict_types=1);

namespace App\Cruding\Contract\Capability;

interface AttachableInterface
{
    public function getAttachments(): iterable;

    public function addAttachment(object $attachment): void;

    public function removeAttachment(object $attachment): void;
}
