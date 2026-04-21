<?php

declare(strict_types=1);

namespace App\Cruding\Contract\Capability;

interface OwnableInterface
{
    public function getOwnerSubject(): mixed;
}
