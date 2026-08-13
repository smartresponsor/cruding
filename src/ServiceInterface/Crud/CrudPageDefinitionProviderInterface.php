<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Dto\Crud\CrudPageDefinition;

interface CrudPageDefinitionProviderInterface
{
    public function provideIndex(CrudContext $context): CrudPageDefinition;

    public function provideShow(CrudContext $context, object $object): CrudPageDefinition;

    public function providePage(CrudContext $context, ?object $object = null): CrudPageDefinition;

    public function provideNew(CrudContext $context, object $object, mixed $formView): CrudPageDefinition;

    public function provideEdit(CrudContext $context, object $object, mixed $formView): CrudPageDefinition;
}
