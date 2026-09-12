<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\DTO\CrudPageDefinitionDTO;

/**
 * Defines the contract for page definition provider within the Cruding component.
 */
interface CrudPageDefinitionProviderInterface
{
    /**      * Provides index.      */
    public function provideIndex(CrudContextDTO $context): CrudPageDefinitionDTO;

    /**      * Provides show.      */
    public function provideShow(CrudContextDTO $context, object $object): CrudPageDefinitionDTO;

    /**      * Provides page.      */
    public function providePage(CrudContextDTO $context, ?object $object = null): CrudPageDefinitionDTO;

    /**      * Provides new.      */
    public function provideNew(CrudContextDTO $context, object $object, mixed $formView): CrudPageDefinitionDTO;

    /**      * Provides edit.      */
    public function provideEdit(CrudContextDTO $context, object $object, mixed $formView): CrudPageDefinitionDTO;
}
