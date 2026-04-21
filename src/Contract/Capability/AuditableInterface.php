<?php

declare(strict_types=1);

namespace App\Cruding\Contract\Capability;

interface AuditableInterface
{
    public function getCreatedAt(): mixed;

    public function getUpdatedAt(): mixed;

    public function getCreatedBy(): mixed;

    public function getUpdatedBy(): mixed;
}
