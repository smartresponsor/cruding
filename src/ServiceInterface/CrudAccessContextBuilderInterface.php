<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface;

use App\Cruding\DTO\CrudAccessContextDTO;
use App\Cruding\DTO\CrudContextDTO;

/**
 * Defines the contract for access context builder within the Cruding component.
 */
interface CrudAccessContextBuilderInterface
{
    /**      * Executes the build operation.      */
    public function build(CrudContextDTO $context, ?object $object = null): CrudAccessContextDTO;
}
