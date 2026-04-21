<?php

declare(strict_types=1);

namespace App\Cruding\Contract\Capability;

interface VisibilityManageableInterface
{
    public function isVisible(): bool;

    public function isPublished(): bool;

    public function isArchived(): bool;

    public function isDraft(): bool;
}
